#!/usr/bin/env python3
"""Generate banner.html (1544x500) for the WordPress.org plugin page banner.

The official Kraken.io hexagon and the official "Kraken.io" wordmark (both from
the brand media kit) are embedded as base64 so the file is self-contained and
can be rendered to PNG at exact size. Layout: the hexagon sits directly left of
the Kraken.io / Image Optimizer lockup, with the tagline and format chips below.
"""
import base64
import os

HERE = os.path.dirname(os.path.abspath(__file__))
ICON = os.path.join(HERE, "official-logos", "krakenio-hexagon-full.png")
# Official "Kraken.io" wordmark in white (cropped from the media kit's
# monochrome horizontal logo) so the banner uses the real logotype, legible on
# the dark background, rather than a font lookalike.
WORDMARK = os.path.join(HERE, "official-logos", "wordmark-white.png")
OUT = os.path.join(HERE, "banner.html")

icon_b64 = base64.b64encode(open(ICON, "rb").read()).decode()
wordmark_b64 = base64.b64encode(open(WORDMARK, "rb").read()).decode()

CHIPS = ["WebP", "AVIF", "JPEG", "PNG", "GIF", "PDF"]
chips_html = "".join('<span class="chip">%s</span>' % c for c in CHIPS)

html = """<!doctype html><html><head><meta charset="utf-8"><style>
*{margin:0;padding:0;box-sizing:border-box}
html,body{width:1544px;height:500px;overflow:hidden}
.banner{position:relative;width:1544px;height:500px;
  background:
    radial-gradient(1100px 700px at 88%% -25%%, rgba(95,170,235,.30), transparent 60%%),
    radial-gradient(700px 500px at 6%% 120%%, rgba(255,150,60,.12), transparent 60%%),
    linear-gradient(118deg,#06192c 0%%,#0d3a64 52%%,#1c6bb0 100%%);
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
  color:#fff;display:flex;align-items:center;padding:0 92px}
.banner::after{content:"";position:absolute;inset:0;
  background:linear-gradient(transparent 70%%,rgba(0,0,0,.18));pointer-events:none}
.content{position:relative;z-index:1;display:flex;flex-direction:column;gap:26px}
.lockup{display:flex;align-items:center;gap:34px}
.hex{width:150px;height:150px;flex:0 0 auto;
  filter:drop-shadow(0 12px 30px rgba(0,0,0,.45))}
.hex img{width:100%%;height:100%%;object-fit:contain}
.names{display:flex;flex-direction:column;gap:10px}
.names img.wordmark{height:74px;display:block}
.title{font-size:38px;font-weight:600;letter-spacing:.5px;color:#eaf4fd}
.tag{font-size:27px;font-weight:500;color:#cfe6fa;max-width:1180px;line-height:1.35}
.tag b{color:#fff;font-weight:700}
.chips{display:flex;gap:12px}
.chip{font-size:20px;font-weight:700;letter-spacing:.4px;padding:7px 18px;border-radius:999px;
  background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.28);color:#eaf4fd}
</style></head><body>
<div class="banner">
  <div class="content">
    <div class="lockup">
      <div class="hex"><img src="data:image/png;base64,%(icon)s" alt=""></div>
      <div class="names">
        <img class="wordmark" src="data:image/png;base64,%(wordmark)s" alt="Kraken.io">
        <div class="title">Image Optimizer</div>
      </div>
    </div>
    <div class="tag">Compress, convert, resize &amp; bulk-optimize your WordPress images — <b>automatically</b>.</div>
    <div class="chips">%(chips)s</div>
  </div>
</div>
</body></html>""" % {"icon": icon_b64, "wordmark": wordmark_b64, "chips": chips_html}

open(OUT, "w", encoding="utf-8").write(html)
print(OUT)
