"""
ゲーム一覧ページ用OGP画像生成スクリプト
ダーク背景+グリッド（ゲーム感） × シンプルレイアウト（ロゴ中央+テキスト）
"""
from PIL import Image, ImageDraw, ImageFont
import os

# ── 設定 ──
W, H = 1200, 630
BG_COLOR = (26, 26, 46)        # ダークネイビー
GRID_COLOR = (50, 50, 70)      # グリッド線
WHITE = (255, 255, 255)
ACCENT = (217, 119, 87)        # オレンジ #d97757
MUTED = (160, 160, 180)

FONT_DIR = "C:/Users/kouse/HOPES-CONSULTING/fonts_tmp"
SILK = os.path.join(FONT_DIR, "Silkscreen-Regular.ttf")
SILK_BOLD = os.path.join(FONT_DIR, "Silkscreen-Bold.ttf")
DOTGOTHIC = os.path.join(FONT_DIR, "DotGothic16-Regular.ttf")
LOGO_PATH = "C:/Users/kouse/HOPES-CONSULTING/images/HOPESCONSULTING_logo.png"
OUT_PATH = "C:/Users/kouse/HOPES-CONSULTING/images/ogp-games.png"

# ── フォント読み込み ──
silk_title = ImageFont.truetype(SILK_BOLD, 42)
silk_sub = ImageFont.truetype(SILK, 24)
dot_desc = ImageFont.truetype(DOTGOTHIC, 22)

img = Image.new("RGB", (W, H), BG_COLOR)
draw = ImageDraw.Draw(img)

# ── グリッド ──
GRID_STEP = 40
for x in range(0, W, GRID_STEP):
    draw.line([(x, 0), (x, H)], fill=GRID_COLOR, width=1)
for y in range(0, H, GRID_STEP):
    draw.line([(0, y), (W, y)], fill=GRID_COLOR, width=1)

# ── ロゴ（中央上部） ──
logo = Image.open(LOGO_PATH).convert("RGBA")
logo_h = 220
logo_w = int(logo.width * logo_h / logo.height)
logo = logo.resize((logo_w, logo_h), Image.LANCZOS)
lx = (W - logo_w) // 2
ly = 50
img.paste(logo, (lx, ly), logo)
draw = ImageDraw.Draw(img)

# ── メインタイトル「HOPES.CONSULTING」──
title = "HOPES.CONSULTING"
tb = draw.textbbox((0, 0), title, font=silk_title)
tw = tb[2] - tb[0]
draw.text(((W - tw) // 2, ly + logo_h + 30), title, fill=WHITE, font=silk_title)

# ── サブテキスト「MINI GAMES & TOOLS」──
sub = "MINI GAMES & TOOLS"
sb = draw.textbbox((0, 0), sub, font=silk_sub)
sw = sb[2] - sb[0]
draw.text(((W - sw) // 2, ly + logo_h + 90), sub, fill=MUTED, font=silk_sub)

# ── 日本語説明 ──
desc = "ミニゲーム・ツール集"
db = draw.textbbox((0, 0), desc, font=dot_desc)
dw = db[2] - db[0]
draw.text(((W - dw) // 2, ly + logo_h + 130), desc, fill=ACCENT, font=dot_desc)

# ── URL ──
url = "WWW.HOPESCONSUL.COM"
ub = draw.textbbox((0, 0), url, font=silk_sub)
uw = ub[2] - ub[0]
draw.text(((W - uw) // 2, ly + logo_h + 175), url, fill=ACCENT, font=silk_sub)

# ── 保存 ──
img.save(OUT_PATH, "PNG", optimize=True)
print(f"OGP saved: {OUT_PATH}")
print(f"Size: {os.path.getsize(OUT_PATH)} bytes")
