#!/usr/bin/env python3
"""Generuje ikony aplikace do assets/icons/ (SVG i PNG ze stejné geometrie).

Spuštění: python3 tools/generate-icons.py [výstupní adresář]
Vyžaduje Pillow.
"""
import math, os, sys
from PIL import Image, ImageDraw

S = 512
GREEN = (27, 94, 32)
WHITE = (255, 255, 255)

def bezier(p0, p1, p2, n=32):
    return [(
        (1-t)**2*p0[0] + 2*(1-t)*t*p1[0] + t*t*p2[0],
        (1-t)**2*p0[1] + 2*(1-t)*t*p1[1] + t*t*p2[1],
    ) for t in (i/n for i in range(n+1))]

def petal(base, tip, bulge_a, bulge_b=None):
    """Closed leaf: out along one bulged curve, back along the other."""
    bulge_b = bulge_a if bulge_b is None else bulge_b
    bx, by = base
    tx, ty = tip
    mx, my = (bx+tx)/2, (by+ty)/2
    dx, dy = tx-bx, ty-by
    length = math.hypot(dx, dy) or 1
    nx, ny = -dy/length, dx/length
    return (bezier(base, (mx + nx*bulge_a, my + ny*bulge_a), tip)
            + bezier(tip, (mx - nx*bulge_b, my - ny*bulge_b), base)[1:])

# Fleur-de-lis: a tall centre petal, two side petals whose tips sweep outward
# and slightly down, a band across the waist and a spike below it.
CENTRE = petal((256, 338), (256, 34), 74)
LEFT   = petal((250, 296), (78, 290), 112, 10)
RIGHT  = [(S-x, y) for x, y in LEFT]
TAIL   = [(228, 348), (284, 348), (256, 466)]
BAR    = (118, 306, 394, 350)
BAR_R  = 22

def mark(size, pad):
    scale = 4
    canvas = Image.new("RGB", (S*scale, S*scale), GREEN)
    d = ImageDraw.Draw(canvas)
    k = (1 - 2*pad) * scale
    off = pad * S * scale
    tx = lambda pts: [(x*k + off, y*k + off) for x, y in pts]

    for shape in (LEFT, RIGHT, CENTRE, TAIL):
        d.polygon(tx(shape), fill=WHITE)
    x0, y0, x1, y1 = BAR
    d.rounded_rectangle([x0*k+off, y0*k+off, x1*k+off, y1*k+off], radius=BAR_R*k, fill=WHITE)
    return canvas.resize((size, size), Image.LANCZOS)

def rounded(img, ratio=0.22):
    mask = Image.new("L", (img.size[0]*4, img.size[1]*4), 0)
    ImageDraw.Draw(mask).rounded_rectangle(
        [0, 0, mask.size[0]-1, mask.size[1]-1], radius=int(mask.size[0]*ratio), fill=255)
    out = img.convert("RGBA")
    out.putalpha(mask.resize(img.size, Image.LANCZOS))
    return out

if __name__ == "__main__":
    out = sys.argv[1] if len(sys.argv) > 1 else "assets/icons"
    os.makedirs(out, exist_ok=True)
    rounded(mark(180, 0.09)).save(f"{out}/icon-180.png")
    rounded(mark(192, 0.09)).save(f"{out}/icon-192.png")
    rounded(mark(512, 0.09)).save(f"{out}/icon-512.png")
    mark(512, 0.19).convert("RGBA").save(f"{out}/icon-512-maskable.png")

    pts = lambda p: " ".join(f"{x:.1f},{y:.1f}" for x, y in p)
    open(f"{out}/icon.svg", "w").write(f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {S} {S}" role="img" aria-label="skautIS mobil">
  <rect width="{S}" height="{S}" rx="112" fill="#1b5e20"/>
  <g fill="#ffffff">
    <polygon points="{pts(LEFT)}"/>
    <polygon points="{pts(RIGHT)}"/>
    <polygon points="{pts(CENTRE)}"/>
    <polygon points="{pts(TAIL)}"/>
    <rect x="{BAR[0]}" y="{BAR[1]}" width="{BAR[2]-BAR[0]}" height="{BAR[3]-BAR[1]}" rx="{BAR_R}"/>
  </g>
</svg>
''')
    print("ok")
