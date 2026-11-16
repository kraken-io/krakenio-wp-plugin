#!/usr/bin/env python3
"""Render readme.txt into a WordPress.org-style HTML preview page.

Mirrors how the wordpress.org plugin directory displays a readme: the readme
sections become TABS (Description / Installation / FAQ / Screenshots / Changelog
/ Upgrade Notice), and screenshots open full-size in a click-to-enlarge
lightbox — both of which wordpress.org generates for you automatically.

Parses the WP readme markdown subset (=== title ===, == section ==, = sub =,
**bold**, *italic*, [text](url "title"), > blockquote, * lists) and embeds the
screenshots from this .wordpress-org directory. Output: readme-preview.html.
"""
import html
import os
import re

HERE = os.path.dirname(os.path.abspath(__file__))
PLUGIN_DIR = os.path.dirname(HERE)
README = os.path.join(PLUGIN_DIR, "readme.txt")
OUT = os.path.join(HERE, "readme-preview.html")


def slug(s):
    return re.sub(r"[^a-z0-9]+", "-", s.lower()).strip("-")


def inline(text):
    """Convert inline readme markdown to HTML (operates on already-escaped text)."""
    text = re.sub(
        r"\[([^\]]+)\]\((\S+?)(?:\s+&quot;[^)]*&quot;)?\)",
        r'<a href="\2" target="_blank" rel="noopener">\1</a>',
        text,
    )
    text = re.sub(r"\*\*([^*]+)\*\*", r"<strong>\1</strong>", text)
    text = re.sub(r"(?<!\*)\*([^*\n]+)\*(?!\*)", r"<em>\1</em>", text)
    text = re.sub(r"`([^`]+)`", r"<code>\1</code>", text)
    return text


def render_body(lines):
    out = []
    i, n = 0, len(lines)
    while i < n:
        line = lines[i].rstrip()
        esc = html.escape(line)

        m = re.match(r"^=\s+(.*?)\s+=$", line)
        if m:
            out.append("<h3>%s</h3>" % inline(html.escape(m.group(1))))
            i += 1
            continue

        if line.startswith(">"):
            buf = []
            while i < n and lines[i].lstrip().startswith(">"):
                buf.append(re.sub(r"^\s*>\s?", "", lines[i].rstrip()))
                i += 1
            out.append("<blockquote>%s</blockquote>" % render_body(buf))
            continue

        if re.match(r"^\*\s+", line):
            items = []
            while i < n and re.match(r"^\*\s+", lines[i]):
                items.append("<li>%s</li>" % inline(html.escape(re.sub(r"^\*\s+", "", lines[i].rstrip()))))
                i += 1
            out.append("<ul>%s</ul>" % "".join(items))
            continue

        if re.match(r"^\d+\.\s+", line):
            items = []
            while i < n and re.match(r"^\d+\.\s+", lines[i]):
                items.append("<li>%s</li>" % inline(html.escape(re.sub(r"^\d+\.\s+", "", lines[i].rstrip()))))
                i += 1
            out.append("<ol>%s</ol>" % "".join(items))
            continue

        if not line.strip():
            i += 1
            continue

        buf = [esc]
        i += 1
        while i < n:
            nxt = lines[i].rstrip()
            if (not nxt.strip()) or nxt.startswith(">") or re.match(r"^[*=]", nxt) or re.match(r"^\d+\.\s", nxt):
                break
            buf.append(html.escape(nxt))
            i += 1
        out.append("<p>%s</p>" % inline(" ".join(buf)))
    return "".join(out)


def main():
    text = open(README, encoding="utf-8").read()
    lines = text.splitlines()

    title = "Plugin"
    m = re.match(r"^===\s+(.*?)\s+===$", lines[0])
    if m:
        title = m.group(1)

    fields, short_desc, body_start = {}, "", 0
    for idx, l in enumerate(lines[1:], start=1):
        if l.startswith("== "):
            body_start = idx
            break
        fm = re.match(r"^([A-Za-z][A-Za-z ]+?):\s*(.*)$", l)
        if fm and fm.group(1) in (
            "Contributors", "Tags", "Requires at least", "Requires PHP",
            "Tested up to", "Donate link", "Stable tag", "License", "License URI",
        ):
            fields[fm.group(1)] = fm.group(2)
        elif l.strip() and not l.startswith("=") and not short_desc:
            short_desc = l.strip()

    sections, cur = [], None
    for l in lines[body_start:]:
        sm = re.match(r"^==\s+(.*?)\s+==$", l)
        if sm:
            cur = {"name": sm.group(1), "lines": []}
            sections.append(cur)
        elif cur is not None:
            cur["lines"].append(l)

    shots = sorted(f for f in os.listdir(HERE) if re.match(r"^screenshot-\d+\.(png|jpg|jpeg|gif)$", f))

    def first_existing(*names):
        for nm in names:
            if os.path.exists(os.path.join(HERE, nm)):
                return nm
        return None

    banner_file = first_existing("banner-1544x500.png", "banner-772x250.png", "banner-1544x500.jpg", "banner-772x250.jpg")
    icon_file = first_existing("icon-256x256.png", "icon-128x128.png", "icon.svg", "icon-256x256.jpg")
    hero_html = '<img class="hero" src="%s" alt="%s banner">' % (banner_file, html.escape(title)) if banner_file else ""
    icon_html = '<img class="plug-icon" src="%s" alt="">' % icon_file if icon_file else ""

    tabs, panels = [], []
    for si, sec in enumerate(sections):
        name = sec["name"]
        sid = slug(name)
        active = " active" if si == 0 else ""
        hidden = "" if si == 0 else " hidden"
        # nicer short tab labels
        label = "FAQ" if name.startswith("Frequently") else name
        tabs.append('<button class="tab%s" data-tab="%s">%s</button>' % (active, sid, html.escape(label)))

        if name == "Screenshots":
            caps = {}
            for l in sec["lines"]:
                cm = re.match(r"^(\d+)\.\s+(.*)$", l.strip())
                if cm:
                    caps[int(cm.group(1))] = cm.group(2)
            cards = []
            for f in shots:
                num = int(re.search(r"(\d+)", f).group(1))
                cap_raw = caps.get(num, "")
                cards.append(
                    '<figure><a class="shot" href="%s" data-cap="%s"><img src="%s" alt="%s" loading="lazy"></a>'
                    '<figcaption>%d. %s</figcaption></figure>'
                    % (f, html.escape(cap_raw), f, html.escape(cap_raw), num, inline(html.escape(cap_raw)))
                )
            body = '<p class="hint">Click any screenshot to enlarge — on wordpress.org this lightbox is generated for you.</p><div class="gallery">%s</div>' % "".join(cards)
        else:
            body = render_body(sec["lines"])

        panels.append('<section class="panel%s" id="%s"%s>%s</section>' % (active, sid, hidden, body))

    # wp.org auto-adds these tabs (not from the readme) — shown disabled for realism
    auto_tabs = "".join('<button class="tab auto" disabled title="Auto-generated by wordpress.org">%s</button>' % t
                        for t in ("Reviews", "Support"))

    tag_html = "".join('<span class="tag">%s</span>' % html.escape(t.strip())
                       for t in fields.get("Tags", "").split(",") if t.strip())
    meta_rows = [
        ("Version", fields.get("Stable tag", "")),
        ("Tested up to", "WordPress " + fields.get("Tested up to", "") if fields.get("Tested up to") else ""),
        ("Requires WordPress", fields.get("Requires at least", "")),
        ("Requires PHP", fields.get("Requires PHP", "")),
        ("License", fields.get("License", "")),
        ("Contributors", fields.get("Contributors", "")),
    ]
    meta_html = "".join(
        '<div class="meta-row"><span class="k">%s</span><span class="v">%s</span></div>' % (k, html.escape(v))
        for k, v in meta_rows if v.strip())

    doc = """<!doctype html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>%(title)s — readme preview</title>
<style>
:root{--blue:#2271b1;--ink:#1d2327;--muted:#646970;--line:#e0e0e0;--bg:#f6f7f7}
*{box-sizing:border-box}
body{margin:0;font:16px/1.65 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:var(--ink);background:var(--bg)}
.preview-note{background:#fff8e5;border-bottom:1px solid #f0e0a8;color:#7a5b00;font-size:13px;text-align:center;padding:7px}
.wrap{max-width:1100px;margin:0 auto;padding:0 24px}
.hero{display:block;width:100%%;max-width:1100px;margin:18px auto 0;border-radius:10px}
.titlebar{background:#fff}
.tb{display:flex;align-items:center;gap:18px;padding:18px 24px}
.plug-icon{width:78px;height:78px;border-radius:16px;box-shadow:0 2px 10px rgba(0,0,0,.18);flex:0 0 auto}
.titlebar h1{margin:0;font-size:26px;line-height:1.25;color:var(--ink)}
.titlebar .sub{margin:5px 0 0;color:var(--muted);font-size:15px}
.tabbar{background:#fff;border-bottom:1px solid var(--line);position:sticky;top:0;z-index:5}
.tabbar .wrap{display:flex;gap:4px;overflow-x:auto}
.tab{appearance:none;background:none;border:0;border-bottom:3px solid transparent;padding:14px 16px;font-size:14px;font-weight:600;color:var(--muted);cursor:pointer;white-space:nowrap}
.tab:hover{color:var(--ink)}
.tab.active{color:var(--blue);border-bottom-color:var(--blue)}
.tab.auto{color:#b0b3b8;cursor:not-allowed;font-weight:500}
.tab.auto::after{content:" ↗";font-size:11px}
.cols{display:grid;grid-template-columns:1fr 300px;gap:32px;margin:28px auto}
@media(max-width:880px){.cols{grid-template-columns:1fr}}
.card{background:#fff;border:1px solid var(--line);border-radius:8px;padding:22px 28px;margin:0 0 22px}
.panel[hidden]{display:none}
h2{font-size:22px;margin:6px 0 10px}
h3{font-size:17px;margin:18px 0 6px;color:#0b3d63}
p{margin:10px 0}ul,ol{margin:10px 0;padding-left:24px}li{margin:5px 0}
a{color:var(--blue);text-decoration:none}a:hover{text-decoration:underline}
code{background:#f0f0f1;border-radius:4px;padding:1px 6px;font-size:88%%;font-family:Menlo,Consolas,monospace}
blockquote{margin:14px 0;padding:12px 18px;border-left:4px solid var(--blue);background:#f0f6fb;border-radius:0 6px 6px 0}
blockquote p{margin:6px 0}
.side .card{position:sticky;top:70px}
.meta-row{display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid #f0f0f1;font-size:14px}
.meta-row:last-child{border-bottom:0}.meta-row .k{color:var(--muted)}.meta-row .v{font-weight:600;text-align:right}
.tags{margin-top:14px;display:flex;flex-wrap:wrap;gap:8px}
.tag{background:#f0f6fb;color:var(--blue);border:1px solid #cfe3f3;border-radius:999px;padding:4px 12px;font-size:13px}
.hint{font-size:13px;color:var(--muted)}
.gallery{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-top:8px}
@media(max-width:880px){.gallery{grid-template-columns:1fr}}
figure{margin:0}
.shot{display:block;cursor:zoom-in}
.shot img{width:100%%;border:1px solid var(--line);border-radius:8px;display:block;box-shadow:0 2px 10px rgba(0,0,0,.06);transition:box-shadow .15s}
.shot:hover img{box-shadow:0 6px 22px rgba(0,0,0,.18)}
figcaption{font-size:13px;color:var(--muted);margin-top:8px}
#lb{position:fixed;inset:0;background:rgba(15,23,32,.92);display:none;align-items:center;justify-content:center;flex-direction:column;z-index:50;cursor:zoom-out;padding:24px}
#lb.open{display:flex}
#lb img{max-width:96vw;max-height:84vh;border-radius:6px;box-shadow:0 10px 40px rgba(0,0,0,.5)}
#lb .cap{color:#fff;margin-top:14px;font-size:14px;opacity:.9;text-align:center;max-width:80vw}
#lb .x{position:absolute;top:18px;right:24px;color:#fff;font-size:30px;line-height:1;opacity:.85}
</style></head>
<body>
<div class="preview-note">Local preview of readme.txt — with the wordpress.org banner + icon assets, sections shown as TABS, and screenshots that open in a lightbox. Not a live wordpress.org page.</div>
%(hero)s
<div class="titlebar"><div class="wrap tb">%(icon)s<div><h1>%(title)s</h1><p class="sub">%(short)s</p></div></div></div>
<div class="tabbar"><div class="wrap">%(tabs)s%(auto_tabs)s</div></div>
<div class="wrap cols">
  <main class="main"><div class="card">%(panels)s</div></main>
  <aside class="side"><div class="card">
    <p style="margin:0 0 6px;font-size:14px;color:var(--muted)">Plugin details</p>
    %(meta)s
    <div class="tags">%(tags)s</div>
  </div></aside>
</div>
<div id="lb"><span class="x">&times;</span><img alt=""><div class="cap"></div></div>
<script>
(function(){
  var tabs=document.querySelectorAll('.tab[data-tab]'),panels=document.querySelectorAll('.panel');
  tabs.forEach(function(t){t.addEventListener('click',function(){
    tabs.forEach(function(x){x.classList.remove('active')});t.classList.add('active');
    var id=t.getAttribute('data-tab');
    panels.forEach(function(p){var on=p.id===id;p.hidden=!on;p.classList.toggle('active',on)});
    window.scrollTo({top:0,behavior:'smooth'});
  })});
  var lb=document.getElementById('lb'),lbImg=lb.querySelector('img'),lbCap=lb.querySelector('.cap');
  document.querySelectorAll('a.shot').forEach(function(a){a.addEventListener('click',function(e){
    e.preventDefault();lbImg.src=a.getAttribute('href');lbCap.textContent=a.getAttribute('data-cap')||'';lb.classList.add('open');
  })});
  lb.addEventListener('click',function(){lb.classList.remove('open');lbImg.src=''});
  document.addEventListener('keydown',function(e){if(e.key==='Escape'){lb.classList.remove('open');lbImg.src=''}});
})();
</script>
</body></html>""" % {
        "title": html.escape(title),
        "short": html.escape(short_desc),
        "hero": hero_html,
        "icon": icon_html,
        "tabs": "".join(tabs),
        "auto_tabs": auto_tabs,
        "panels": "".join(panels),
        "meta": meta_html,
        "tags": tag_html,
    }

    open(OUT, "w", encoding="utf-8").write(doc)
    print(OUT)


if __name__ == "__main__":
    main()
