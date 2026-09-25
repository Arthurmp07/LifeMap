"""Gera as variantes do logo LifeMap a partir do arquivo original (que não é alterado).

O logo foi desenhado sobre um fundo escuro quase opaco (uma "mancha" cinza-escura ao redor
do símbolo e do nome). Em vez de tentar recortá-lo, as variantes mantêm esse fundo:

  logo-completa.png      original recortado (sem a margem vazia): para superfícies escuras
  logo-simbolo.png       só o símbolo, num ícone de cantos arredondados com o fundo do logo
  favicon.png            64x64 do ícone
  apple-touch-icon.png   180x180 em quadrado inteiro (iOS arredonda sozinho)
"""
import sys
from pathlib import Path
import numpy as np
from PIL import Image, ImageDraw

pasta = Path(sys.argv[1])
original = Image.open(pasta / 'logo-original.png').convert('RGBA')
a = np.array(original).astype(np.int16)
alfa = a[..., 3]
brilho = a[..., :3].max(axis=2)

# ---------- cor do fundo do logo: a mancha escura quase opaca ao redor do conteúdo ----------
mancha = (alfa >= 200) & (alfa <= 253) & (brilho < 24)
fundo_rgb = tuple(int(np.median(a[..., c][mancha])) for c in range(3))
print('cor do fundo do logo:', fundo_rgb, '#%02x%02x%02x' % fundo_rgb)


def sobre_fundo(imagem):
    base = Image.new('RGBA', imagem.size, fundo_rgb + (255,))
    base.alpha_composite(imagem)
    return base


# ---------- logo completa (recorte do original) ----------
ys, xs = np.nonzero(alfa > 10)
completa = original.crop((xs.min(), ys.min(), xs.max() + 1, ys.max() + 1))
completa = completa.resize((880, round(completa.height * 880 / completa.width)), Image.LANCZOS)
completa.save(pasta / 'logo-completa.png', optimize=True)
print('logo-completa', completa.size)

# ---------- símbolo: caixa dos pixels vivos acima do texto, com margem ----------
vivo = (alfa >= 250) & (brilho > 90)
ys, xs = np.nonzero(vivo[:720])
sx0, sy0, sx1, sy1 = xs.min(), ys.min(), xs.max() + 1, ys.max() + 1
lado = int(max(sx1 - sx0, sy1 - sy0) * 1.30)          # margem de 15% de cada lado
cx, cy = (sx0 + sx1) // 2, (sy0 + sy1) // 2
recorte = sobre_fundo(original).crop((cx - lado // 2, cy - lado // 2, cx + lado // 2, cy + lado // 2))


def arredondar(imagem, tamanho, raio_pct):
    """Redimensiona e aplica cantos arredondados (com suavização por supersampling)."""
    s = 4
    grande = imagem.resize((tamanho * s, tamanho * s), Image.LANCZOS)
    mascara = Image.new('L', grande.size, 0)
    ImageDraw.Draw(mascara).rounded_rectangle((0, 0, grande.width - 1, grande.height - 1), radius=int(grande.width * raio_pct), fill=255)
    mascara = mascara.resize((tamanho, tamanho), Image.LANCZOS)
    saida = grande.resize((tamanho, tamanho), Image.LANCZOS)
    saida.putalpha(mascara)
    return saida


arredondar(recorte, 256, 0.22).save(pasta / 'logo-simbolo.png', optimize=True)
arredondar(recorte, 64, 0.22).save(pasta / 'favicon.png', optimize=True)
recorte.resize((180, 180), Image.LANCZOS).convert('RGB').save(pasta / 'apple-touch-icon.png', optimize=True)
print('logo-simbolo 256, favicon 64, apple-touch-icon 180')
