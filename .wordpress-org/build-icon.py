#!/usr/bin/env python3
"""Generate icon.html for the WordPress.org plugin icon: the official Kraken.io
hexagon (brand media kit) centered on the same dark-blue treatment as the
banner, so the directory icon and the banner read as one brand. Render to
icon-256x256.png / icon-128x128.png via capture-icon.js.
"""
import base64
import os

HERE = os.path.dirname(os.path.abspath(__file__))
ICON = os.path.join(HERE, "official-logos", "krakenio-hexagon-full.png")
OUT = os.path.join(HERE, "icon.html")

icon_b64 = base64.b64encode(open(ICON, "rb").read()).decode()

html = """<!doctype html><html><head><meta charset="utf-8"><style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{width:256px;height:256px;overflow:hidden}
.icon{position:relative;width:256px;height:256px;display:flex;align-items:center;justify-content:center;
  background:
    radial-gradient(circle at 50%% 32%%, #1f5798 0%%, #0c2c52 58%%, #07203c 100%%);}
.hex{width:200px;height:200px;filter:drop-shadow(0 8px 18px rgba(0,0,0,.45))}
.hex img{width:100%%;height:100%%;object-fit:contain}
</style></head><body>
<div class="icon"><div class="hex"><img src="data:image/png;base64,%(icon)s" alt=""></div></div>
</body></html>""" % {"icon": icon_b64}

open(OUT, "w", encoding="utf-8").write(html)
print(OUT)
