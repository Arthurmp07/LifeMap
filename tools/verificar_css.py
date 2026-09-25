"""Confere o CSS do modo escuro: rode depois de editar assets/css/style.css.

  python tools/verificar_css.py

Verifica que (1) toda variável usada está definida, (2) cada token que muda com o modo tem o
valor escuro nos dois blocos (@media prefers-color-scheme e [data-theme="dark"]) e (3) esses
dois blocos são idênticos. Sai com código 1 se algo estiver errado.
"""
import re
import sys
from pathlib import Path

sys.stdout.reconfigure(encoding='utf-8')  # o console do Windows usa cp1252 por padrão

css = (Path(__file__).resolve().parent.parent / 'assets/css/style.css').read_text(encoding='utf-8')
problemas = []

definidas = set(re.findall(r'(--[\w-]+)\s*:', css))
usadas = set(re.findall(r'var\((--[\w-]+)', css))
if usadas - definidas:
    problemas.append('variáveis usadas e nunca definidas: ' + ', '.join(sorted(usadas - definidas)))

def valores(bloco):
    return dict(re.findall(r'(--[\w-]+)\s*:\s*([^;]+);', bloco))

claro = valores(re.search(r':root \{(.*?)\n\}', css, re.S).group(1))
escuro_media = valores(re.search(r'@media \(prefers-color-scheme: dark\) \{\s*:root:not\(\[data-theme="light"\]\) \{(.*?)\n    \}', css, re.S).group(1))
escuro_attr = valores(re.search(r':root\[data-theme="dark"\] \{(.*?)\n\}', css, re.S).group(1))

if escuro_media != escuro_attr:
    dif = sorted(set(escuro_media.items()) ^ set(escuro_attr.items()))
    problemas.append('os dois blocos escuros divergem: ' + ', '.join(sorted({d[0] for d in dif})))

# tokens "por modo" = os que aparecem no bloco escuro; todos devem existir também no claro
faltam_no_claro = set(escuro_media) - set(claro)
if faltam_no_claro:
    problemas.append('tokens escuros sem valor claro: ' + ', '.join(sorted(faltam_no_claro)))

print(f'{len(claro)} tokens no claro, {len(escuro_media)} no escuro (por modo), {len(definidas)} variáveis definidas no total')
if problemas:
    print('PROBLEMAS:\n - ' + '\n - '.join(problemas))
    sys.exit(1)
print('CSS ok: blocos escuros idênticos e nenhuma variável sem definição.')
