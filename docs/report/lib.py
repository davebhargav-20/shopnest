"""Helpers for the report generator: figures, tags, tables, inline SVG diagrams, CSS."""
import html

fig_n = [0]
SS = "../screenshots/"


def figure(img, caption, w="12.2cm"):
    fig_n[0] += 1
    return (f'<figure><img src="{SS}{img}" style="width:{w}">'
            f'<figcaption><b>Figure {fig_n[0]}.</b> {caption}</figcaption></figure>')


def fig_svg(svgtxt, caption):
    fig_n[0] += 1
    return f'<figure>{svgtxt}<figcaption><b>Figure {fig_n[0]}.</b> {caption}</figcaption></figure>'


def tag(t):
    cls = {"V": "v", "I": "i", "D": "d", "N": "n"}[t]
    txt = {"V": "Verified", "I": "Implemented, not tested", "D": "Designed only", "N": "Not done"}[t]
    return f'<span class="tag {cls}">{txt}</span>'


def table(head, rows):
    h = "".join(f"<th>{c}</th>" for c in head)
    b = "".join("<tr>" + "".join(f"<td>{c}</td>" for c in r) + "</tr>" for r in rows)
    return f"<table><thead><tr>{h}</tr></thead><tbody>{b}</tbody></table>"


def box(x, y, w, h, text, fill="#e8f0fe", stroke="#3b6fd4", size=12):
    lines = text.split("\n")
    ty = y + h / 2 - (len(lines) - 1) * 7 + 4
    t = "".join(
        f'<text x="{x + w / 2}" y="{ty + i * 14}" text-anchor="middle" font-size="{size}" fill="#1b2a4a">{html.escape(l)}</text>'
        for i, l in enumerate(lines))
    return f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="6" fill="{fill}" stroke="{stroke}"/>{t}'


def arrow(x1, y1, x2, y2, label="", dash=False):
    d = ' stroke-dasharray="5,4"' if dash else ""
    l = (f'<text x="{(x1 + x2) / 2 + 4}" y="{(y1 + y2) / 2 - 4}" font-size="10" fill="#555">{html.escape(label)}</text>'
         if label else "")
    return f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="#444" stroke-width="1.4" marker-end="url(#a)"{d}/>{l}'


DEFS = ('<defs><marker id="a" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto">'
        '<path d="M0,0 L8,4 L0,8 z" fill="#444"/></marker></defs>')


def svg(w, h, body):
    return (f'<svg viewBox="0 0 {w} {h}" width="100%" xmlns="http://www.w3.org/2000/svg" '
            f'font-family="Segoe UI,Arial">{DEFS}{body}</svg>')


CSS = """
@page { size: A4; margin: 18mm 16mm 20mm 16mm; }
body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 10.5pt; line-height: 1.45; color: #1d2433; }
h1 { font-size: 20pt; color: #17325f; border-bottom: 2px solid #3b6fd4; padding-bottom: 4px; margin-top: 0; page-break-before: always; }
h1.first { page-break-before: avoid; }
h2 { font-size: 13.5pt; color: #24457f; margin-bottom: 4px; }
table { border-collapse: collapse; width: 100%; margin: 8px 0 12px; font-size: 9.2pt; }
th { background: #17325f; color: #fff; text-align: left; padding: 4px 6px; }
td { border: 1px solid #c9d2e3; padding: 4px 6px; vertical-align: top; }
tr { page-break-inside: avoid; }
figure { margin: 8px 0 10px; text-align: center; page-break-inside: avoid; }
figure img { border: 1px solid #b8c2d6; }
figcaption { font-size: 8.8pt; color: #333; text-align: left; margin: 3px 14mm 0 14mm; }
.tag { font-size: 8pt; padding: 1px 6px; border-radius: 9px; color: #fff; white-space: nowrap; }
.v { background: #2f9e57; } .i { background: #d98a1a; } .d { background: #6b7a99; } .n { background: #c0392b; }
.cover { text-align: center; padding-top: 55mm; }
.cover h1 { border: none; font-size: 30pt; page-break-before: avoid; }
.note { background: #fff7e0; border-left: 4px solid #d98a1a; padding: 6px 10px; margin: 8px 0; font-size: 9.6pt; }
code { background: #eef1f7; padding: 0 3px; font-size: 9pt; }
"""


def page(title, body):
    return (f"<!doctype html><html><head><meta charset='utf-8'><title>{title}</title>"
            f"<style>{CSS}</style></head><body>{body}</body></html>")


# ---- diagrams ----
arch = svg(760, 400,
    box(10, 170, 90, 50, "Users\n(browser)", "#fff4d6", "#c99a1a") +
    box(160, 120, 230, 150, "EC2 t3.micro (us-east-1)\nDocker: Caddy :80 -> PHP-Apache app\nuploads on EBS gp3 (encrypted)\nsystemd timer: pull-deploy") +
    box(500, 170, 130, 50, "RDS MySQL 8.4\nprivate, encrypted, TLS", "#e3f6e8", "#2f9e57") +
    box(500, 20, 130, 44, "S3 backups\n(SSE, versioned)", "#e3f6e8", "#2f9e57") +
    box(160, 20, 130, 44, "CloudWatch Logs\n+ 5 alarms", "#f3e8fd", "#8a4fd0") +
    box(330, 20, 130, 44, "SNS topic\n(no subscriber yet)", "#f3e8fd", "#8a4fd0") +
    box(160, 320, 130, 50, "SSM: Run Command\n+ Parameter Store", "#f3e8fd", "#8a4fd0") +
    box(330, 320, 140, 50, "EventBridge -> Lambda\nnightly auto-stop", "#f3e8fd", "#8a4fd0") +
    box(600, 320, 150, 50, "GitHub Actions CI\nlint, test, build, Trivy", "#eeeeee", "#666") +
    arrow(100, 195, 160, 195, "HTTP 80") + arrow(390, 195, 500, 195, "3306 TLS") +
    arrow(390, 150, 500, 50, "nightly dump", True) + arrow(225, 120, 225, 64, "logs") + arrow(290, 42, 330, 42, "alarm") +
    arrow(225, 320, 225, 270, "", True) + arrow(400, 320, 330, 270, "stop", True) +
    arrow(600, 345, 390, 255, "polled by server", True))

cicd = svg(760, 250,
    box(5, 20, 100, 50, "git push\n(branch)", "#fff4d6", "#c99a1a") +
    box(140, 20, 120, 50, "Lint: php -l,\ngitleaks, terraform") + box(290, 20, 120, 50, "Test: e2e\n68 checks (MariaDB)") +
    box(440, 20, 150, 50, "Build: hadolint, Trivy\nCRITICAL, smoke test") +
    box(620, 20, 130, 50, "CI result\n(public on GitHub)", "#eeeeee", "#666") +
    box(140, 150, 150, 60, "EC2 timer (2 min)\nlatest commit?\nCI success for SHA?", "#e8f0fe") +
    box(330, 150, 130, 60, "Build image\non server", "#e8f0fe") + box(490, 150, 120, 60, "Swap + health\ncheck", "#e8f0fe") +
    box(640, 110, 110, 40, "DEPLOYED", "#e3f6e8", "#2f9e57") + box(640, 170, 110, 40, "ROLLBACK", "#fde8e8", "#c0392b") +
    arrow(105, 45, 140, 45) + arrow(260, 45, 290, 45) + arrow(410, 45, 440, 45) + arrow(590, 45, 620, 45) +
    arrow(685, 70, 215, 150, "server asks GitHub", True) + arrow(290, 180, 330, 180) + arrow(460, 180, 490, 180) +
    arrow(610, 170, 640, 130, "ok") + arrow(610, 190, 640, 190, "fail"))

flow = svg(760, 190,
    box(10, 20, 220, 70, "CUSTOMER\nbrowse/search -> cart -> checkout (COD)\n-> track -> cancel -> review", "#e8f0fe") +
    box(270, 20, 220, 70, "SELLER (admin approves shop)\nproducts + images -> receives orders\n-> updates item status -> sales report", "#e3f6e8", "#2f9e57") +
    box(530, 20, 220, 70, "ADMIN\napprove/block sellers, categories,\nall orders, customers, reports", "#fff4d6", "#c99a1a") +
    box(120, 120, 520, 50, "One MySQL database (RDS): one order may contain items from several sellers", "#f5f5f5", "#888") +
    arrow(120, 90, 250, 120) + arrow(380, 90, 380, 120) + arrow(640, 90, 510, 120))
