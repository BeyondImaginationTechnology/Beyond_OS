from pathlib import Path
from PIL import Image, ImageDraw, ImageFont, ImageFilter

ROOT = Path(__file__).resolve().parents[1]
ICON = ROOT / "Resources/Assets.xcassets/AppIcon.appiconset/AppIcon-1024.png"
OUT = ROOT / "AppStoreAssets/Screenshots"

BG = (8, 11, 13)
PANEL = (24, 30, 32)
PANEL_SOFT = (34, 42, 45)
MINT = (61, 219, 163)
BLUE = (46, 110, 235)
GOLD = (240, 189, 87)
MUTED = (168, 181, 184)
WHITE = (255, 255, 255)

SHOTS = [
    ("01-wallet-balance.png", "BEYOND WALLET", "Your wallet balance, open at a glance.", "View bit$ rewards, USD/CAD value, watchlist count, and weekly micro ideas without signing in.", "wallet"),
    ("02-market-watchlist.png", "FREE MARKETS", "Track stocks and crypto before you commit.", "Follow prices, movers, and watchlist assets with a clean finance-first snapshot.", "markets"),
    ("03-weekly-micro-ideas.png", "MICRO IDEAS", "$5-$10 weekly investing prompts.", "Educational stock and crypto ideas for learning-sized amounts, clearly labeled by risk.", "micro"),
    ("04-crypto-wallet-view.png", "WATCH-ONLY CRYPTO", "View public wallet balances gate-free.", "Track BTC, ETH, and SOL public addresses without storing seed phrases or private keys.", "crypto"),
    ("05-beyond-id-premium.png", "BEYOND ID", "Unlock premium wallet controls securely.", "Cash, card controls, managing watched addresses, and full ledger history require Beyond ID.", "gate"),
]

SPECS = [
    ("iPhone-6.5", (1242, 2688), (212, 712, 1030, 2438), (104, 124, 1138, 658)),
    ("iPad-13", (2064, 2752), (1000, 430, 1880, 2290), (150, 360, 870, 1400)),
]


def font(size, bold=False):
    candidates = [
        "/System/Library/Fonts/SFNS.ttf",
        "/System/Library/Fonts/SFCompact.ttf",
        "/System/Library/Fonts/Supplemental/Arial Bold.ttf" if bold else "/System/Library/Fonts/Supplemental/Arial.ttf",
    ]
    for path in candidates:
        try:
            return ImageFont.truetype(path, size=size)
        except Exception:
            continue
    return ImageFont.load_default(size=size)


def rounded(draw, xy, radius, fill, outline=None, width=1):
    draw.rounded_rectangle(xy, radius=radius, fill=fill, outline=outline, width=width)


def glow(base, center, radius, color, alpha):
    layer = Image.new("RGBA", base.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    for i in range(34, 0, -1):
        pct = i / 34
        a = int(alpha * pct * pct)
        r = int(radius * pct)
        d.ellipse((center[0] - r, center[1] - r, center[0] + r, center[1] + r), fill=(*color, a))
    base.alpha_composite(layer.filter(ImageFilter.GaussianBlur(radius // 10)))


def gradient_round(base, xy, radius):
    x1, y1, x2, y2 = map(int, xy)
    w, h = x2 - x1, y2 - y1
    grad = Image.new("RGBA", (w, h))
    pix = grad.load()
    for y in range(h):
        for x in range(w):
            t = (x / max(1, w) + y / max(1, h)) / 2
            c = tuple(int(BLUE[i] * (1 - t) + MINT[i] * t) for i in range(3))
            pix[x, y] = (*c, 255)
    mask = Image.new("L", (w, h), 0)
    ImageDraw.Draw(mask).rounded_rectangle((0, 0, w, h), radius=radius, fill=255)
    base.paste(grad, (x1, y1), mask)


def text(draw, value, xy, size, fill=WHITE, bold=False, anchor=None, align="left", spacing=4):
    draw.multiline_text(xy, value, font=font(size, bold), fill=fill, anchor=anchor, align=align, spacing=spacing)


def wrap(value, max_chars):
    words, lines, line = value.split(), [], ""
    for word in words:
        trial = f"{line} {word}".strip()
        if len(trial) > max_chars and line:
            lines.append(line)
            line = word
        else:
            line = trial
    if line:
        lines.append(line)
    return "\n".join(lines)


def pill(draw, xy, label, fill, color=WHITE, size=22):
    rounded(draw, xy, (xy[3] - xy[1]) // 2, fill)
    text(draw, label, ((xy[0] + xy[2]) // 2, (xy[1] + xy[3]) // 2), size, color, True, anchor="mm", align="center")


def metric(draw, xy, title, value):
    rounded(draw, xy, 20, PANEL_SOFT)
    text(draw, title.upper(), (xy[0] + 18, xy[1] + 16), 17, MUTED, True)
    text(draw, value, (xy[0] + 18, xy[1] + 50), 28, WHITE, True)


def panel(draw, xy, eyebrow, title):
    rounded(draw, xy, 24, PANEL)
    text(draw, eyebrow.upper(), (xy[0] + 26, xy[1] + 25), 21, MINT, True)
    text(draw, wrap(title, 30), (xy[0] + 26, xy[1] + 68), 31, WHITE, True, spacing=4)


def circle_label(draw, xy, label, fill, color):
    draw.ellipse(xy, fill=fill)
    text(draw, label, ((xy[0] + xy[2]) // 2, (xy[1] + xy[3]) // 2), 20, color, True, anchor="mm")


def market_row(draw, safe, y, symbol, name, price, change, up=True, starred=False):
    x1, _, x2, _ = safe
    xy = (x1, y, x2, y + 128)
    rounded(draw, xy, 22, PANEL)
    circle_label(draw, (x1 + 24, y + 32, x1 + 88, y + 96), symbol, (*MINT, 40), MINT)
    text(draw, name, (x1 + 112, y + 26), 28, WHITE, True)
    text(draw, "Watchlist" if starred else "Market", (x1 + 112, y + 66), 21, MUTED, True)
    text(draw, price, (x2 - 30, y + 26), 27, WHITE, True, anchor="ra")
    text(draw, change, (x2 - 30, y + 72), 22, MINT if up else GOLD, True, anchor="ra")


def idea_row(draw, safe, y, symbol, title_value, amount, risk, color):
    x1, _, x2, _ = safe
    xy = (x1, y, x2, y + 148)
    rounded(draw, xy, 22, PANEL)
    circle_label(draw, (x1 + 24, y + 42, x1 + 88, y + 106), symbol, (*color, 38), color)
    text(draw, title_value, (x1 + 112, y + 34), 28, WHITE, True)
    text(draw, risk, (x1 + 112, y + 78), 22, color, True)
    text(draw, amount, (x2 - 30, y + 54), 31, WHITE, True, anchor="ra")


def crypto_row(draw, safe, y, symbol, name, address):
    x1, _, x2, _ = safe
    xy = (x1, y, x2, y + 142)
    rounded(draw, xy, 22, PANEL)
    circle_label(draw, (x1 + 24, y + 39, x1 + 88, y + 103), symbol, (*MINT, 40), MINT)
    text(draw, name, (x1 + 112, y + 35), 28, WHITE, True)
    text(draw, address, (x1 + 112, y + 78), 21, MUTED, True)
    text(draw, "Watch-only", (x2 - 30, y + 58), 21, MINT, True, anchor="ra")


def draw_header(draw, safe):
    x1, y1, x2, _ = safe
    text(draw, "BEYOND WALLET", (x1, y1 + 16), 28, WHITE, True)
    text(draw, "Rewards, markets, cash, card, and crypto", (x1, y1 + 56), 20, MUTED, True)
    pill(draw, (x2 - 78, y1 + 23, x2, y1 + 67), "1.0", (*MINT, 42), MINT, 19)


def draw_screen(base, phone, kind):
    draw = ImageDraw.Draw(base)
    rounded(draw, phone, 74, (0, 0, 0, 138))
    inner = (phone[0] + 20, phone[1] + 20, phone[2] - 20, phone[3] - 20)
    rounded(draw, inner, 58, BG)
    safe = (phone[0] + 58, phone[1] + 70, phone[2] - 58, phone[3] - 70)
    draw_header(draw, safe)
    x1, y1, x2, _ = safe

    if kind == "wallet":
        hero = (x1, y1 + 132, x2, y1 + 632)
        gradient_round(base, hero, 26)
        text(draw, "BEYOND BITS", (hero[0] + 30, hero[1] + 28), 22, (235, 255, 249), True)
        text(draw, "1,250 bit$", (hero[0] + 30, hero[1] + 110), 68, WHITE, True)
        text(draw, "Closed-loop rewards travel with your Beyond ID.", (hero[0] + 30, hero[1] + 220), 26, (220, 246, 240), True)
        metric(draw, (hero[0] + 30, hero[1] + 340, hero[0] + 330, hero[1] + 444), "Earned", "1,850 bit$")
        metric(draw, (hero[0] + 360, hero[1] + 340, hero[0] + 660, hero[1] + 444), "Spent", "600 bit$")
        panel(draw, (x1, hero[3] + 28, x2, hero[3] + 238), "Value comparison", "100 bit$ = US$1.00")
        metric(draw, (x1 + 24, hero[3] + 134, x1 + 324, hero[3] + 226), "USD estimate", "$12.50")
        metric(draw, (x1 + 356, hero[3] + 134, x1 + 656, hero[3] + 226), "CAD estimate", "$17.12")
        panel(draw, (x1, hero[3] + 270, x2, hero[3] + 480), "Free market tools", "Watchlist, movers, and $5-$10 ideas")
    elif kind == "markets":
        panel(draw, (x1, y1 + 132, x2, y1 + 308), "Free markets", "Stocks and crypto before you commit")
        metric(draw, (x1, y1 + 335, x1 + 318, y1 + 439), "Watchlist", "4 assets")
        metric(draw, (x1 + 346, y1 + 335, x2, y1 + 439), "Top mover", "SOL")
        market_row(draw, safe, y1 + 480, "BTC", "Bitcoin", "$68,420.18", "+1.82%", True, True)
        market_row(draw, safe, y1 + 635, "ETH", "Ethereum", "$3,585.40", "-0.44%", False, True)
        market_row(draw, safe, y1 + 790, "SOL", "Solana", "$172.91", "+2.16%", True, False)
        market_row(draw, safe, y1 + 945, "AAPL", "Apple", "$224.75", "+0.62%", True, True)
    elif kind == "micro":
        panel(draw, (x1, y1 + 132, x2, y1 + 322), "Weekly micro ideas", "$5-$10 learning-sized options")
        idea_row(draw, safe, y1 + 360, "BTC", "$5 Bitcoin starter", "$5.00", "High risk", GOLD)
        idea_row(draw, safe, y1 + 540, "AAPL", "$10 fractional stock idea", "$10.00", "Medium risk", BLUE)
        idea_row(draw, safe, y1 + 720, "ETH", "$5 smart-contract watch", "$5.00", "High risk", GOLD)
        panel(draw, (x1, y1 + 935, x2, y1 + 1125), "Educational prompts", "Not personalized advice or trade instructions.")
    elif kind == "crypto":
        panel(draw, (x1, y1 + 132, x2, y1 + 322), "Watch-only crypto", "View public wallet balances gate-free")
        crypto_row(draw, safe, y1 + 365, "ETH", "Main wallet", "0x742d35...38f44e")
        crypto_row(draw, safe, y1 + 540, "SOL", "Solana vault", "7wDNw9vW...N8HHz")
        panel(draw, (x1, y1 + 760, x2, y1 + 970), "Beyond ID required", "Adding or removing watched addresses is account-linked.")
        panel(draw, (x1, y1 + 1010, x2, y1 + 1220), "Private keys never belong here", "No seed phrases, no custody, no watch-only transfers.")
    else:
        card = (x1, y1 + 170, x2, y1 + 770)
        rounded(draw, card, 28, PANEL)
        text(draw, "BEYOND ID REQUIRED", (card[0] + 34, card[1] + 48), 24, MINT, True)
        text(draw, wrap("Unlock premium wallet controls", 20), (card[0] + 34, card[1] + 116), 48, WHITE, True, spacing=4)
        text(draw, wrap("Cash, card controls, address management, and full ledger history stay protected until a Beyond ID session is active.", 42), (card[0] + 34, card[1] + 292), 25, MUTED, True, spacing=5)
        pill(draw, (card[0] + 34, card[1] + 474, card[2] - 34, card[1] + 548), "Connect Beyond ID", MINT, BG, 24)
        panel(draw, (x1, card[3] + 38, x2, card[3] + 268), "Gate-free stays useful", "Balance, markets, micro ideas, and public wallet viewing remain open.")


def draw_copy(base, shot, copy_box, compact):
    draw = ImageDraw.Draw(base)
    x1, y1, x2, _ = copy_box
    icon_size = 92 if compact else 112
    if ICON.exists():
        icon = Image.open(ICON).convert("RGB").resize((icon_size, icon_size))
        mask = Image.new("L", (icon_size, icon_size), 0)
        ImageDraw.Draw(mask).rounded_rectangle((0, 0, icon_size, icon_size), radius=22, fill=255)
        base.paste(icon, (x1, y1), mask)
    text(draw, shot[1], (x1, y1 + icon_size + 38), 30 if compact else 34, MINT, True)
    max_chars = 23 if compact else 18
    text(draw, wrap(shot[2], max_chars), (x1, y1 + icon_size + 92), 76 if compact else 78, WHITE, True, spacing=0)
    sub_y = y1 + icon_size + (330 if compact else 420)
    text(draw, wrap(shot[3], 52 if compact else 36), (x1, sub_y), 35, MUTED, True, spacing=8)


def render(shot, spec):
    name, size, phone, copy_box = spec
    base = Image.new("RGBA", size, (*BG, 255))
    glow(base, (int(size[0] * 0.18), int(size[1] * 0.17)), int(size[0] * 0.55), MINT, 88)
    glow(base, (int(size[0] * 0.86), int(size[1] * 0.72)), int(size[0] * 0.50), BLUE, 68)
    draw_copy(base, shot, copy_box, name.startswith("iPhone"))
    draw_screen(base, phone, shot[4])
    draw = ImageDraw.Draw(base)
    text(draw, "Market data and investing ideas shown for education only.", (size[0] // 2, size[1] - 96), 23 if name.startswith("iPhone") else 25, MUTED, True, anchor="mm", align="center")
    final = base.convert("RGB")
    out_dir = OUT / name
    out_dir.mkdir(parents=True, exist_ok=True)
    out_path = out_dir / shot[0]
    final.save(out_path, "PNG", optimize=True)
    print(out_path)


for shot in SHOTS:
    for spec in SPECS:
        render(shot, spec)
