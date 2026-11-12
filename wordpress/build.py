"""Gera wordpress/dist/prints-cartoes.zip (plugin pronto para enviar no WordPress).

Junta o código do plugin (wordpress/prints-cartoes/) com os assets do site (assets/),
assim CSS, JS, dados e imagens têm uma única fonte. Rode após editar qualquer um deles:

    python wordpress/build.py
"""
import pathlib
import zipfile

RAIZ = pathlib.Path(__file__).resolve().parent.parent
PLUGIN = RAIZ / "wordpress" / "prints-cartoes"
ASSETS = RAIZ / "assets"
SAIDA = RAIZ / "wordpress" / "dist" / "prints-cartoes.zip"


def adicionar(z, origem, destino):
    for arq in sorted(origem.rglob("*")):
        if arq.is_file() and arq.name not in (".DS_Store", "Thumbs.db"):
            z.write(arq, f"prints-cartoes/{destino}{arq.relative_to(origem).as_posix()}")


SAIDA.parent.mkdir(parents=True, exist_ok=True)
with zipfile.ZipFile(SAIDA, "w", zipfile.ZIP_DEFLATED) as z:
    adicionar(z, PLUGIN, "")
    adicionar(z, ASSETS, "assets/")

with zipfile.ZipFile(SAIDA) as z:
    for nome in z.namelist():
        print(nome)
print(f"\nOK: {SAIDA.relative_to(RAIZ)} ({SAIDA.stat().st_size / 1e6:.1f} MB)")
