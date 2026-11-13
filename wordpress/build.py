"""Gera os arquivos do WordPress em wordpress/dist/ e o índice do site estático.

  <plugin>.zip           plugin (instalar UMA vez: Plugins > Adicionar novo > Enviar plugin)
  pagina-<pagina>.zip    pacote de cada página (Ferramentas > <plugin> > Importar)
  paginas-todas.zip      todas as páginas num pacote só
  ../index.html          lista das páginas (raiz do site estático / GitHub Pages)
  demo-blueprint.json    (se existir) passa a importar paginas-todas.zip e abrir na primeira página

Páginas = pastas na raiz do projeto que têm dados.js (ex.: cartoes/dados.js + cartoes/img/).
O CSS e o JS do formulário vêm de assets/ (os mesmos do site estático). Rode depois de editar:

    python wordpress/build.py
"""
import html
import json
import pathlib
import re
import sys
import zipfile

RAIZ = pathlib.Path(__file__).resolve().parent.parent
PLUGIN = next(p for p in (RAIZ / "wordpress").iterdir() if p.is_dir() and (p / f"{p.name}.php").exists())
DIST = RAIZ / "wordpress" / "dist"
COMPARTILHADOS = ["assets/css/style.css", "assets/js/formulario.js"]
IGNORAR = {".DS_Store", "Thumbs.db"}
EXTERNO = re.compile(r"^(https?:)?//")


def ler_dados(arquivo):
    """Lê window.FORM_CONFIG = {...} de um dados.js."""
    texto = pathlib.Path(arquivo).read_text(encoding="utf-8")
    m = re.search(r"\{.*\}", texto, re.S)
    if not m:
        sys.exit(f"{arquivo}: não encontrei o objeto FORM_CONFIG")
    return json.loads(m.group(0))


def paginas():
    """Lista de (pasta, dados) de cada página do projeto, em ordem alfabética."""
    achadas = []
    for pasta in sorted(RAIZ.iterdir()):
        if pasta.is_dir() and (pasta / "dados.js").exists():
            dados = ler_dados(pasta / "dados.js")
            dados.setdefault("slug", pasta.name)
            dados.setdefault("titulo", pasta.name)
            achadas.append((pasta, dados))
    return achadas


def gerar_plugin():
    saida = DIST / f"{PLUGIN.name}.zip"
    with zipfile.ZipFile(saida, "w", zipfile.ZIP_DEFLATED) as z:
        for arq in sorted(PLUGIN.rglob("*")):
            if arq.is_file() and arq.name not in IGNORAR:
                z.write(arq, f"{PLUGIN.name}/{arq.relative_to(PLUGIN).as_posix()}")
        for rel in COMPARTILHADOS:
            z.write(RAIZ / rel, f"{PLUGIN.name}/{rel}")
    return saida


def gerar_pacote(lista, saida):
    """lista: (pasta, dados). Imagens de dados são relativas à pasta da página."""
    with zipfile.ZipFile(saida, "w", zipfile.ZIP_DEFLATED) as z:
        for pasta, dados in lista:
            slug = dados["slug"]
            dados = json.loads(json.dumps(dados))
            dados["formato"] = 1
            gravadas = set()
            for campo in dados["campos"]:
                img = campo.get("imagem")
                if not img or EXTERNO.match(img):
                    continue
                origem = pathlib.Path(pasta) / img
                if not origem.is_file():
                    sys.exit(f"imagem não encontrada: {origem}")
                destino = f"img/{origem.name}"
                if destino not in gravadas:
                    z.write(origem, f"paginas/{slug}/{destino}")
                    gravadas.add(destino)
                campo["imagem"] = destino
            z.writestr(f"paginas/{slug}/pagina.json", json.dumps(dados, ensure_ascii=False, indent=2))
    return saida


def gerar_indice(lista):
    """index.html da raiz: links para as páginas (site estático)."""
    itens = "\n".join(
        f'      <li><a href="{html.escape(p.name)}/">{html.escape(d["titulo"])}</a></li>' for p, d in lista
    )
    modelo = (RAIZ / "wordpress" / "indice.html").read_text(encoding="utf-8")
    (RAIZ / "index.html").write_text(modelo.replace("<!-- PAGINAS -->", itens), encoding="utf-8", newline="\n")


def atualizar_blueprint(lista):
    """Demonstração no WordPress Playground: importa todas as páginas e abre a primeira."""
    arq = RAIZ / "wordpress" / "demo-blueprint.json"
    if not arq.exists():
        return
    bp = json.loads(arq.read_text(encoding="utf-8"))
    bp["landingPage"] = f"/{lista[0][1]['slug']}/"
    for passo in bp.get("steps", []):
        dados = passo.get("data") or {}
        if passo.get("step") == "writeFile" and dados.get("url", "").endswith(".zip"):
            dados["url"] = dados["url"].rsplit("/", 1)[0] + "/paginas-todas.zip"
            passo["path"] = "/tmp/paginas.zip"
        if passo.get("step") == "runPHP" and "_importar_zip(" in passo.get("code", ""):
            passo["code"] = re.sub(r"_importar_zip\('[^']*'", "_importar_zip('/tmp/paginas.zip'", passo["code"])
    arq.write_text(json.dumps(bp, ensure_ascii=False, indent=2) + "\n", encoding="utf-8", newline="\n")


if __name__ == "__main__":
    DIST.mkdir(parents=True, exist_ok=True)
    lista = paginas()
    if not lista:
        sys.exit("Nenhuma página: crie uma pasta com dados.js (ver README).")
    saidas = [gerar_plugin()]
    for pasta, dados in lista:
        saidas.append(gerar_pacote([(pasta, dados)], DIST / f"pagina-{dados['slug']}.zip"))
    saidas.append(gerar_pacote(lista, DIST / "paginas-todas.zip"))
    gerar_indice(lista)
    atualizar_blueprint(lista)
    for arq in saidas:
        with zipfile.ZipFile(arq) as z:
            print(f"{arq.relative_to(RAIZ)}  ({arq.stat().st_size / 1e6:.2f} MB, {len(z.namelist())} arquivos)")
    print(f"index.html  ({len(lista)} página(s): {', '.join(d['slug'] for _, d in lista)})")
