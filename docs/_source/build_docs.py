"""Build the OGNIVID AI Assistant documentation (English + Bahasa Indonesia).

Usage:  python3 -I docs/_source/build_docs.py
Writes: docs/OGNIVID-AI-Assistant-Documentation.html and docs/id/OGNIVID-AI-Assistant-Dokumentasi.html

Every text is a pair T(en, id) so both languages always have the same structure.
"""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
OUT = {"en": ROOT / "docs" / "OGNIVID-AI-Assistant-Documentation.html",
       "id": ROOT / "docs" / "id" / "OGNIVID-AI-Assistant-Dokumentasi.html"}
LINK = {"en": "OGNIVID-AI-Assistant-Documentation.html", "id": "id/OGNIVID-AI-Assistant-Dokumentasi.html"}
SITE = "https://ognivid.com/"
REPO = "https://github.com/diimasogan/ognivid-ai-assistant"
LANG = "en"


class T(tuple):
    def __new__(cls, en, id_=None):
        return super().__new__(cls, (en, en if id_ is None else id_))

    def __str__(self):
        return self[0] if LANG == "en" else self[1]


def s(x):
    return str(x) if isinstance(x, T) else x


# ---------------------------------------------------------------- building blocks
def icon(path):
    return f'<svg class="ico" viewBox="0 0 24 24">{path}</svg>'


ICONS = {
    "clock": '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    "shield": '<path d="M12 3l8 3v6c0 4.5-3.4 8.3-8 9-4.6-.7-8-4.5-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
    "users": '<circle cx="9" cy="8" r="3.5"/><path d="M2 20c.8-3.5 3.6-5.5 7-5.5s6.2 2 7 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7"/>',
    "doc": '<path d="M4 4h11l5 5v11H4z"/><path d="M15 4v5h5M8 13h8M8 17h5"/>',
    "chat": '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>',
    "check": '<path d="M20 6 9 17l-5-5"/>',
    "lock": '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    "list": '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
    "bolt": '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
    "eye": '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
    "globe": '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>',
    "layers": '<path d="m12 3 9 5-9 5-9-5z"/><path d="m3 13 9 5 9-5"/>',
}


def card(ic, title, text, amber=False):
    return (f'<div class="card{" amber" if amber else ""}"><div class="ihead">{icon(ICONS[ic])}<h3>{s(title)}</h3></div>'
            f'<p>{s(text)}</p></div>')


def grid(cards, cols=3):
    return f'<div class="grid g{cols}">{"".join(cards)}</div>'


def table(head, rows):
    h = "".join(f"<th>{s(c)}</th>" for c in head)
    b = "".join("<tr>" + "".join(f"<td>{s(c)}</td>" for c in r) + "</tr>" for r in rows)
    return f'<div class="tbl"><table><thead><tr>{h}</tr></thead><tbody>{b}</tbody></table></div>'


def section(sid, title, body):
    return f'<section id="{sid}"><h2>{s(title)}</h2>{body}</section>'


def stats(items):
    return '<div class="stats">' + "".join(f'<div class="stat"><b>{n}</b><span>{s(t)}</span></div>' for n, t in items) + "</div>"


def ul(items):
    return '<ul class="clean">' + "".join(f"<li>{s(i)}</li>" for i in items) + "</ul>"



# ---------------------------------------------------------------- diagram
def architecture_svg():
    L = lambda en, id_: s(T(en, id_))
    return f'''<figure><svg viewBox="0 0 980 440" role="img" aria-label="{L('Solution architecture','Arsitektur solusi')}">
<defs><marker id="ar" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0 10 5 0 10z" class="mk"/></marker></defs>
<g class="lane"><rect x="10" y="20" width="200" height="400" rx="14"/><text x="110" y="46" class="lt">{L('Visitor','Pengunjung')}</text></g>
<g class="lane"><rect x="230" y="20" width="470" height="400" rx="14"/><text x="465" y="46" class="lt">WordPress · ognivid.com</text></g>
<g class="lane"><rect x="720" y="20" width="250" height="400" rx="14"/><text x="845" y="46" class="lt">{L('External services','Layanan eksternal')}</text></g>
<g class="bx hl"><rect x="30" y="80" width="160" height="80" rx="10"/><text x="110" y="108">{L('Chat widget','Widget chat')}</text><text x="110" y="128" class="sm">widget.js · EN / ID</text><text x="110" y="146" class="sm">{L('chat or form','chat atau formulir')}</text></g>
<g class="bx"><rect x="30" y="300" width="160" height="62" rx="10"/><text x="110" y="326">{L('Customer inbox','Inbox pelanggan')}</text><text x="110" y="346" class="sm">{L('copy + PDF','salinan + PDF')}</text></g>
<g class="bx"><rect x="250" y="80" width="200" height="80" rx="10"/><text x="350" y="106">REST API</text><text x="350" y="126" class="sm">/ognivid-ai/v1/chat</text><text x="350" y="144" class="sm">/ognivid-ai/v1/form</text></g>
<g class="bx amb"><rect x="250" y="180" width="200" height="80" rx="10"/><text x="350" y="206">{L('Guards','Pengaman')}</text><text x="350" y="226" class="sm">{L('origin · rate limits','origin · batas pesan')}</text><text x="350" y="244" class="sm">{L('honeypot · test mode','honeypot · mode uji')}</text></g>
<g class="bx"><rect x="250" y="280" width="200" height="80" rx="10"/><text x="350" y="306">{L('Server-side session','Sesi di server')}</text><text x="350" y="326" class="sm">{L('transient · 2 h','transient · 2 jam')}</text><text x="350" y="344" class="sm">{L('history never trusted','riwayat tidak dari browser')}</text></g>
<g class="bx hl"><rect x="480" y="80" width="200" height="80" rx="10"/><text x="580" y="106">{L('Provider adapter','Adapter provider')}</text><text x="580" y="126" class="sm">Claude Messages API</text><text x="580" y="144" class="sm">{L('or OpenAI-compatible','atau OpenAI-compatible')}</text></g>
<g class="bx"><rect x="480" y="180" width="200" height="80" rx="10"/><text x="580" y="206">submit_project_brief</text><text x="580" y="226" class="sm">{L('strict JSON schema','skema JSON ketat')}</text><text x="580" y="244" class="sm">{L('validated on server','divalidasi di server')}</text></g>
<g class="bx hl"><rect x="480" y="280" width="200" height="110" rx="10"/><text x="580" y="306">{L('Brief pipeline','Pipeline brief')}</text><text x="580" y="328" class="sm">{L('AI Briefs (private post type)','AI Briefs (post type privat)')}</text><text x="580" y="348" class="sm">{L('PDF (FPDF, private folder)','PDF (FPDF, folder privat)')}</text><text x="580" y="368" class="sm">{L('HTML email via wp_mail','Email HTML via wp_mail')}</text></g>
<g class="bx"><rect x="740" y="80" width="210" height="80" rx="10"/><text x="845" y="106">Anthropic API</text><text x="845" y="126" class="sm">claude-opus-5-5 ({L('default','bawaan')})</text><text x="845" y="144" class="sm">{L('refusal fallback on','fallback refusal aktif')}</text></g>
<g class="bx"><rect x="740" y="180" width="210" height="62" rx="10"/><text x="845" y="206">Gemini · Groq · OpenRouter</text><text x="845" y="226" class="sm">{L('optional alternatives','alternatif opsional')}</text></g>
<g class="bx"><rect x="740" y="300" width="210" height="62" rx="10"/><text x="845" y="326">SMTP</text><text x="845" y="346" class="sm">{L('WP Mail SMTP → owner inbox','WP Mail SMTP → inbox pemilik')}</text></g>
<g class="ln"><path d="M190 120h60" marker-end="url(#ar)"/><path d="M350 160v20" marker-end="url(#ar)"/><path d="M350 260v20" marker-end="url(#ar)"/>
<path d="M450 300h15v-180h15" marker-end="url(#ar)"/><path d="M680 120h60" marker-end="url(#ar)"/><path d="M680 135h30v76h30" marker-end="url(#ar)"/>
<path d="M580 160v20" marker-end="url(#ar)"/><path d="M580 260v20" marker-end="url(#ar)"/><path d="M680 335h60" marker-end="url(#ar)"/><path d="M480 380H170v-18" marker-end="url(#ar)"/></g>
</svg><figcaption>{L('The browser only sends the visitor’s message. Conversation history, limits, the API key and brief validation all stay on the server.','Browser hanya mengirim pesan pengunjung. Riwayat percakapan, batas pesan, API key, dan validasi brief semuanya tetap di server.')}</figcaption></figure>'''


def steps(items):
    out = ""
    for i, (title, text, amber) in enumerate(items, 1):
        out += (f'<div class="card starter"><div class="snum{" amber" if amber else ""}">{i}</div>'
                f'<div><p class="q">{s(title)}</p><p>{s(text)}</p></div></div>')
    return f'<div class="grid g2">{out}</div>'


def content():
    return [
        section("summary", T("Executive summary", "Ringkasan eksekutif"),
            f'<p class="lead">{s(T("OGNIVID AI Assistant is a WordPress plugin that turns website visitors into well-defined project leads. An AI assistant interviews the visitor like an experienced consultant, summarises the need, asks for confirmation and sends a structured Project Brief to the OGNIVID team as an HTML email with a PDF attached.", "OGNIVID AI Assistant adalah plugin WordPress yang mengubah pengunjung website menjadi calon klien dengan kebutuhan yang jelas. Asisten AI mewawancarai pengunjung layaknya konsultan berpengalaman, merangkum kebutuhannya, meminta konfirmasi, lalu mengirim Project Brief terstruktur ke tim OGNIVID sebagai email HTML dengan lampiran PDF."))}</p>'
            f'<p>{s(T("It runs on Claude by default, can switch to other AI providers, and falls back to a short form whenever AI is unavailable, so a request is never lost. Every brief is also stored privately in wp-admin.", "Secara bawaan plugin memakai Claude, bisa dipindah ke penyedia AI lain, dan otomatis beralih ke formulir singkat saat AI tidak tersedia, sehingga tidak ada permintaan yang hilang. Setiap brief juga tersimpan secara privat di wp-admin."))}</p>'
            + stats([("2", T("modes: AI chat & form", "mode: chat AI & formulir")), ("16", T("structured brief fields", "kolom brief terstruktur")),
                     ("9", T("AI providers + form-only mode", "penyedia AI + mode formulir")), ("2", T("languages (EN / ID)", "bahasa (EN / ID)"))])),

        section("how", T("How it works", "Cara kerja"),
            steps([(T("Visitor opens the chat", "Pengunjung membuka chat"), T("A floating “Talk to our AI” button appears on every page, in the language of the page.", "Tombol “Konsultasi dengan AI” muncul di setiap halaman, sesuai bahasa halaman."), False),
                   (T("AI interviews the visitor", "AI mewawancarai pengunjung"), T("One or two focused questions at a time: problem, goals, current systems, constraints, urgency, timeline, budget and contact details.", "Satu atau dua pertanyaan terarah setiap kali: masalah, tujuan, sistem saat ini, batasan, urgensi, target waktu, anggaran, dan data kontak."), False),
                   (T("Visitor confirms the summary", "Pengunjung mengonfirmasi ringkasan"), T("The assistant shows a short summary of the brief and only continues after the visitor confirms or corrects it.", "Asisten menampilkan ringkasan singkat dan baru melanjutkan setelah pengunjung mengonfirmasi atau mengoreksinya."), True),
                   (T("Brief is submitted", "Brief dikirim"), T("The model calls the submit_project_brief tool with a strict JSON schema; the server validates name and email before accepting it.", "Model memanggil tool submit_project_brief dengan skema JSON ketat; server memvalidasi nama dan email sebelum menerimanya."), False),
                   (T("Brief is stored and sent", "Brief disimpan dan dikirim"), T("Saved under AI Briefs, rendered as a PDF and emailed to OGNIVID (with the transcript) and to the customer (with a WhatsApp link).", "Disimpan di AI Briefs, dibuat PDF, lalu dikirim ke OGNIVID (beserta transkrip) dan ke pelanggan (beserta link WhatsApp)."), False),
                   (T("Form fallback", "Formulir cadangan"), T("If AI is off, out of credit, failing or over the daily limit, the same brief is collected with a short form.", "Jika AI mati, saldo habis, error, atau melewati batas harian, brief yang sama dikumpulkan lewat formulir singkat."), True)])
            + '<figure style="margin-top:18px"><img src="' + ("" if LANG == "en" else "../") + 'assets/widget-chat-' + LANG + '.png" alt="' + s(T("Chat widget on ognivid.com", "Widget chat di ognivid.com")) + '" style="display:block;width:100%;height:auto;min-width:0;border-radius:10px"><figcaption>' + s(T("The chat widget on the English site. On the Indonesian site the same widget speaks Indonesian.", "Widget chat di situs bahasa Indonesia. Di situs bahasa Inggris, widget yang sama berbahasa Inggris.")) + '</figcaption></figure>'),

        section("architecture", T("Solution architecture", "Arsitektur solusi"), architecture_svg()),

        section("brief", T("The Project Brief", "Project Brief"),
            f'<p class="lead">{s(T("Every brief, from AI or from the form, uses the same schema, written in the customer’s language.", "Setiap brief, baik dari AI maupun formulir, memakai skema yang sama dan ditulis dalam bahasa pelanggan."))}</p>'
            + table([T("Field", "Kolom"), T("Content", "Isi")], [
                ["<code>customer</code>", T("Name, email, company, phone/WhatsApp, role", "Nama, email, perusahaan, telepon/WhatsApp, jabatan")],
                ["<code>title</code> · <code>summary</code>", T("Short project title and a 2–3 sentence executive summary", "Judul proyek singkat dan ringkasan eksekutif 2–3 kalimat")],
                ["<code>problem</code>", T("The problem in the customer’s own terms", "Masalah dalam kata-kata pelanggan sendiri")],
                ["<code>goals</code> · <code>requirements</code>", T("What success looks like and what the solution must do", "Seperti apa keberhasilan dan apa yang harus dilakukan solusi")],
                ["<code>current_systems</code>", T("Platforms and tools already in use", "Platform dan tools yang sudah dipakai")],
                ["<code>constraints</code>", T("Deadline, budget, compliance, security", "Tenggat, anggaran, kepatuhan, keamanan")],
                ["<code>urgency</code>", T("low · medium · high · critical (high/critical are flagged in the email subject)", "low · medium · high · critical (high/critical ditandai di subjek email)")],
                ["<code>timeline</code> · <code>budget</code>", T("Desired timeline and budget range", "Target waktu dan kisaran anggaran")],
                ["<code>suggested_services</code>", T("Managed File Transfer, Integration &amp; AI Agents, Data &amp; Dashboards, Other", "Managed File Transfer, Integration &amp; AI Agents, Data &amp; Dashboards, Lainnya")],
                ["<code>initial_recommendation</code>", T("A short, non-binding first idea", "Ide awal singkat yang tidak mengikat")],
                ["<code>open_questions</code>", T("What the team still needs to clarify", "Hal yang masih perlu diklarifikasi tim")],
            ])),

        section("features", T("Features", "Fitur"),
            grid([card("chat", T("Consultative interview", "Wawancara konsultatif"), T("Short replies, focused follow-ups, about 6–10 questions, then a summary to confirm.", "Balasan singkat, pertanyaan lanjutan yang terarah, sekitar 6–10 pertanyaan, lalu ringkasan untuk dikonfirmasi.")),
                  card("doc", T("Email + PDF", "Email + PDF"), T("A clean HTML email and an A4 PDF for the team, plus a copy for the customer.", "Email HTML yang rapi dan PDF A4 untuk tim, plus salinan untuk pelanggan.")),
                  card("list", T("Admin inbox", "Inbox di wp-admin"), T("AI Briefs lists every brief with customer, urgency, source and status, an unread badge in the menu, a status filter, the full transcript, a protected PDF download, internal notes and one-click reply by email or WhatsApp.", "AI Briefs menampilkan setiap brief beserta pelanggan, urgensi, sumber, dan status, badge pesan baru di menu, filter status, transkrip lengkap, unduhan PDF yang terlindungi, catatan internal, serta balas sekali klik lewat email atau WhatsApp.")),
                  card("globe", T("Bilingual", "Dua bahasa"), T("Follows the page language (English or Indonesian) and switches with the visitor.", "Mengikuti bahasa halaman (Inggris atau Indonesia) dan berpindah mengikuti pengunjung.")),
                  card("layers", T("Provider-agnostic", "Tidak terkunci provider"), T("Claude by default; OpenAI (ChatGPT models), Google Gemini, Mistral, DeepSeek, xAI Grok, Groq, OpenRouter or any OpenAI-compatible endpoint as alternatives.", "Claude secara bawaan; OpenAI (model ChatGPT), Google Gemini, Mistral, DeepSeek, xAI Grok, Groq, OpenRouter, atau endpoint OpenAI-compatible lain sebagai alternatif.")),
                  card("bolt", T("Never loses a lead", "Tidak kehilangan calon klien"), T("Automatic form fallback when AI is unavailable or a limit is reached.", "Formulir cadangan otomatis saat AI tidak tersedia atau batas tercapai."), True)])),

        section("security", T("Security &amp; privacy", "Keamanan &amp; privasi"),
            grid([card("lock", T("Key stays on the server", "Key tetap di server"), T("The API key is stored in WordPress options, never sent to the browser and never echoed back in the settings page.", "API key disimpan di opsi WordPress, tidak pernah dikirim ke browser, dan tidak ditampilkan kembali di halaman pengaturan.")),
                  card("shield", T("Server-side history", "Riwayat di server"), T("Conversations live in a server transient; visitors cannot inject fake assistant turns. Claude’s thinking blocks are replayed unchanged.", "Percakapan disimpan di transient server; pengunjung tidak bisa menyisipkan balasan asisten palsu. Blok thinking Claude dikirim ulang tanpa diubah.")),
                  card("eye", T("Abuse limits", "Batas penyalahgunaan"), T("Same-origin check, messages per conversation, messages per IP per hour, daily site-wide AI cap, honeypot and minimum fill time on the form.", "Cek same-origin, batas pesan per percakapan, pesan per IP per jam, batas AI harian per situs, honeypot, dan waktu isi minimum di formulir.")),
                  card("check", T("Strict structured output", "Output terstruktur ketat"), T("The brief tool uses a strict schema and is re-validated and sanitised on the server before anything is saved or sent.", "Tool brief memakai skema ketat dan divalidasi serta disanitasi ulang di server sebelum disimpan atau dikirim.")),
                  card("doc", T("Private PDFs", "PDF privat"), T("PDFs are written to a denied folder in uploads and downloaded only through a nonce-protected admin link.", "PDF disimpan di folder uploads yang diblokir dan hanya bisa diunduh lewat link admin yang dilindungi nonce.")),
                  card("users", T("Honest with visitors", "Transparan ke pengunjung"), T("The widget states that the chat is processed by AI and asks visitors not to share passwords or confidential data.", "Widget menyatakan bahwa chat diproses oleh AI dan meminta pengunjung tidak membagikan password atau data rahasia."), True)])),

        section("install", T("Installation", "Instalasi"),
            ul([T("Download <code>ognivid-ai-assistant.zip</code> from the latest <a href=\"https://github.com/diimasogan/ognivid-ai-assistant/releases/latest\">GitHub release</a> (or <code>git clone</code> the repository into <code>wp-content/plugins/ognivid-ai-assistant</code>).", "Unduh <code>ognivid-ai-assistant.zip</code> dari <a href=\"https://github.com/diimasogan/ognivid-ai-assistant/releases/latest\">rilis GitHub terbaru</a> (atau <code>git clone</code> repository ke <code>wp-content/plugins/ognivid-ai-assistant</code>)."),
                T("In wp-admin go to <strong>Plugins → Add New → Upload Plugin</strong>, upload the ZIP and activate <strong>OGNIVID AI Assistant</strong>.", "Di wp-admin buka <strong>Plugins → Add New → Upload Plugin</strong>, unggah ZIP-nya, lalu aktifkan <strong>OGNIVID AI Assistant</strong>."),
                T("Open <strong>Settings → OGNIVID AI Assistant</strong>. The plugin starts in <strong>test mode</strong> (admins only).", "Buka <strong>Settings → OGNIVID AI Assistant</strong>. Plugin dimulai dalam <strong>mode uji</strong> (hanya admin)."),
                T("Choose an AI provider, paste its API key (for example from console.anthropic.com or platform.openai.com), set the recipient email and save. Without a key the assistant works in form mode.", "Pilih penyedia AI, tempel API key-nya (misalnya dari console.anthropic.com atau platform.openai.com), atur email penerima, lalu simpan. Tanpa key, asisten berjalan dalam mode formulir."),
                T("Make sure WordPress can send email (for example with WP Mail SMTP), test a brief, then turn test mode off.", "Pastikan WordPress bisa mengirim email (misalnya dengan WP Mail SMTP), uji satu brief, lalu matikan mode uji."),
                T("Follow up from <strong>AI Briefs</strong>: new briefs are bold and counted in the menu badge; opening one marks it as read, and the Follow-up box tracks the status (New, Read, Contacted, Proposal sent, Won, Closed) and an internal note.", "Tindak lanjuti dari <strong>AI Briefs</strong>: brief baru ditampilkan tebal dan dihitung di badge menu; membukanya menandainya sebagai sudah dibaca, dan kotak Follow-up mencatat status (New, Read, Contacted, Proposal sent, Won, Closed) serta catatan internal.")])
            + f'<p class="note">{s(T("Requirements: WordPress 6.x, PHP 7.4+ (tested on PHP 8.2), outbound HTTPS from the server, and working email delivery.", "Kebutuhan: WordPress 6.x, PHP 7.4+ (diuji di PHP 8.2), koneksi HTTPS keluar dari server, dan pengiriman email yang berfungsi."))}</p>'),

        section("usage", T("How to use", "Cara penggunaan"),
            '<h3>' + s(T("Visitors", "Pengunjung")) + '</h3>'
            + ul([T("Click <strong>Talk to our AI</strong> (bottom left) on any page.", "Klik <strong>Konsultasi dengan AI</strong> (kiri bawah) di halaman mana pun."),
                  T("Describe the project and answer the assistant’s questions. Prefer not to chat? Click <strong>Prefer a short form?</strong>.", "Ceritakan proyeknya dan jawab pertanyaan asisten. Tidak ingin chat? Klik <strong>Lebih suka isi formulir?</strong>."),
                  T("Check the summary, confirm it, and receive a reference number on screen plus a copy of the brief by email.", "Periksa ringkasannya, konfirmasi, lalu terima nomor referensi di layar dan salinan brief lewat email.")])
            + '<h3 style="margin-top:18px">' + s(T("Site owner", "Pemilik situs")) + '</h3>'
            + ul([T("A new brief arrives by email (with PDF) and appears in <strong>wp-admin → AI Briefs</strong> with a number badge in the menu.", "Brief baru masuk lewat email (dengan PDF) dan muncul di <strong>wp-admin → AI Briefs</strong> dengan angka di menu."),
                  T("Open the brief to read it (it is marked as read), download the PDF and read the conversation transcript.", "Buka brief untuk membacanya (otomatis ditandai sudah dibaca), unduh PDF, dan baca transkrip percakapannya."),
                  T("Use <strong>Reply by email</strong> or <strong>Chat on WhatsApp</strong> in the Follow-up box, then set the status (Contacted, Proposal sent, Won, Closed) and add an internal note.", "Pakai <strong>Reply by email</strong> atau <strong>Chat on WhatsApp</strong> di kotak Follow-up, lalu atur status (Contacted, Proposal sent, Won, Closed) dan tambahkan catatan internal."),
                  T("Use the status filter above the list to see only new or open briefs.", "Pakai filter status di atas daftar untuk melihat brief baru atau yang masih terbuka saja.")])
            + '<h3 style="margin-top:18px">' + s(T("Switching AI provider", "Mengganti penyedia AI")) + '</h3>'
            + ul([T("Choose the provider in <strong>Settings → OGNIVID AI Assistant</strong>, paste that provider’s API key and type one of its model IDs, then save.", "Pilih penyedia di <strong>Settings → OGNIVID AI Assistant</strong>, tempel API key dari penyedia tersebut dan ketik salah satu ID modelnya, lalu simpan."),
                  T("Open the site as admin and send a test message. If the chat switches to the form, the key, model ID or credit is wrong; the reason is in the PHP error log.", "Buka situs sebagai admin dan kirim pesan uji. Jika chat berpindah ke formulir, berarti key, ID model, atau saldonya bermasalah; penyebabnya ada di PHP error log.")])),

        section("settings", T("Settings reference", "Referensi pengaturan"),
            table([T("Setting", "Pengaturan"), T("Default", "Bawaan"), T("Purpose", "Fungsi")], [
                [T("Show the assistant", "Tampilkan asisten"), "on", T("Master switch for the widget", "Saklar utama widget")],
                [T("Test mode", "Mode uji"), "on", T("Only logged-in administrators see and can use the assistant", "Hanya administrator yang login yang bisa melihat dan memakai asisten")],
                [T("AI provider", "Penyedia AI"), "Anthropic", T("Anthropic, OpenAI, Gemini, Mistral, DeepSeek, xAI, Groq, OpenRouter, custom OpenAI-compatible, or Off (form only)", "Anthropic, OpenAI, Gemini, Mistral, DeepSeek, xAI, Groq, OpenRouter, OpenAI-compatible kustom, atau Off (hanya formulir)")],
                [T("Model", "Model"), "<code>claude-opus-5-5</code>", T("Any model ID of the selected provider; change it when you switch provider (for example a GPT model for OpenAI). Claude Sonnet 5.5 / Haiku 5.5 cost less than Opus.", "ID model apa pun dari penyedia terpilih; ganti saat berpindah penyedia (misalnya model GPT untuk OpenAI). Claude Sonnet 5.5 / Haiku 5.5 lebih hemat daripada Opus.")],
                [T("Effort (Claude)", "Effort (Claude)"), "medium", T("Thinking depth: low, medium or high", "Kedalaman berpikir: low, medium, atau high")],
                [T("Send briefs to", "Kirim brief ke"), "hello@ognivid.com", T("Owner inbox; optional copy to the customer", "Inbox pemilik; salinan opsional ke pelanggan")],
                [T("WhatsApp number", "Nomor WhatsApp"), "62852…", T("Follow-up link shown to customers", "Link tindak lanjut untuk pelanggan")],
                [T("Button position", "Posisi tombol"), T("bottom left", "kiri bawah"), T("Avoids overlapping a WhatsApp button on the right", "Menghindari tumpang tindih dengan tombol WhatsApp di kanan")],
                [T("Limits", "Batas"), "25 · 1500 · 40 · 60", T("Messages per conversation · characters per message · messages per IP per hour · AI conversations per day", "Pesan per percakapan · karakter per pesan · pesan per IP per jam · percakapan AI per hari")],
            ])),

        section("providers", T("AI providers", "Penyedia AI"),
            table([T("Provider", "Penyedia"), T("How it is called", "Cara pemanggilan"), T("Notes", "Catatan")], [
                ["Anthropic Claude", T("Messages API (<code>/v1/messages</code>, version <code>2026-10-21</code>) with a strict tool, prompt caching and server-side refusal fallback", "Messages API (<code>/v1/messages</code>, versi <code>2026-10-21</code>) dengan tool ketat, prompt caching, dan fallback refusal di server"), T("Recommended. Pay as you go; set a monthly spend limit in the Console.", "Disarankan. Bayar sesuai pemakaian; atur batas pengeluaran bulanan di Console.")],
                ["OpenAI (ChatGPT)", T("Chat Completions API (<code>api.openai.com/v1/chat/completions</code>) with function calling", "Chat Completions API (<code>api.openai.com/v1/chat/completions</code>) dengan function calling"), T("Use the GPT model IDs from your OpenAI account. Key: platform.openai.com → API keys. A ChatGPT Plus subscription does not include API access.", "Pakai ID model GPT dari akun OpenAI Anda. Key: platform.openai.com → API keys. Langganan ChatGPT Plus tidak termasuk akses API.")],
                ["Google Gemini", T("OpenAI-compatible endpoint", "Endpoint OpenAI-compatible"), T("Has a free tier; check its data-use terms before sending customer data. Key: aistudio.google.com.", "Ada versi gratis; cek ketentuan penggunaan datanya sebelum mengirim data pelanggan. Key: aistudio.google.com.")],
                ["Mistral AI", T("OpenAI-compatible endpoint (<code>api.mistral.ai</code>)", "Endpoint OpenAI-compatible (<code>api.mistral.ai</code>)"), T("European provider. Key: console.mistral.ai.", "Penyedia asal Eropa. Key: console.mistral.ai.")],
                ["DeepSeek", T("OpenAI-compatible endpoint (<code>api.deepseek.com</code>)", "Endpoint OpenAI-compatible (<code>api.deepseek.com</code>)"), T("Low cost. Key: platform.deepseek.com.", "Biaya rendah. Key: platform.deepseek.com.")],
                ["xAI (Grok)", T("OpenAI-compatible endpoint (<code>api.x.ai</code>)", "Endpoint OpenAI-compatible (<code>api.x.ai</code>)"), T("Grok models. Key: console.x.ai.", "Model Grok. Key: console.x.ai.")],
                ["Groq · OpenRouter", T("OpenAI-compatible endpoint", "Endpoint OpenAI-compatible"), T("Open-source models, free quotas that change often. OpenRouter gives access to hundreds of models (including GPT, Claude, Llama) with one key.", "Model open source, kuota gratis yang sering berubah. OpenRouter memberi akses ke ratusan model (termasuk GPT, Claude, Llama) dengan satu key.")],
                [T("Custom", "Kustom"), T("Any OpenAI-compatible <code>/chat/completions</code> URL", "URL <code>/chat/completions</code> OpenAI-compatible apa pun"), T("For self-hosted models (e.g. Ollama behind a gateway).", "Untuk model self-hosted (misalnya Ollama di balik gateway).")],
                ["Off", T("No AI", "Tanpa AI"), T("Form only, free forever.", "Hanya formulir, gratis selamanya.")],
            ])),

        section("api", T("REST API", "REST API"),
            table([T("Endpoint", "Endpoint"), T("Request (JSON)", "Request (JSON)"), T("Response", "Response")], [
                ["<code>POST /wp-json/ognivid-ai/v1/chat</code>", "<code>{ sid, message, lang }</code>", T("<code>{ sid, reply, done, mode }</code>, where <code>mode: \"form\"</code> asks the widget to show the form", "<code>{ sid, reply, done, mode }</code>, dengan <code>mode: \"form\"</code> meminta widget menampilkan formulir")],
                ["<code>POST /wp-json/ognivid-ai/v1/form</code>", "<code>{ name, email, company, phone, title, problem, systems, service, urgency, timeline, budget, lang, ts, website }</code>", "<code>{ ok, reply }</code>"],
            ])
            + f'<p class="note">{s(T("Both endpoints accept same-origin requests only. In test mode they also require a logged-in administrator (the widget sends the REST nonce).", "Kedua endpoint hanya menerima request dari situs yang sama. Dalam mode uji, keduanya juga membutuhkan administrator yang login (widget mengirim REST nonce)."))}</p>'),

        section("components", T("Components", "Komponen"),
            table([T("File", "File"), T("Responsibility", "Tanggung jawab")], [
                ["<code>ognivid-ai-assistant.php</code>", T("Plugin bootstrap and hooks", "Bootstrap plugin dan hook")],
                ["<code>includes/class-oaa-settings.php</code>", T("Settings page, defaults and sanitising", "Halaman pengaturan, nilai bawaan, dan sanitasi")],
                ["<code>includes/class-oaa-provider.php</code>", T("System prompt, brief schema, Claude and OpenAI-compatible adapters", "System prompt, skema brief, adapter Claude dan OpenAI-compatible")],
                ["<code>includes/class-oaa-chat.php</code>", T("REST endpoints, sessions, limits, widget loading", "Endpoint REST, sesi, batas, pemuatan widget")],
                ["<code>includes/class-oaa-brief.php</code>", T("AI Briefs post type, PDF, HTML email, admin view", "Post type AI Briefs, PDF, email HTML, tampilan admin")],
                ["<code>assets/widget.js</code> · <code>widget.css</code>", T("Chat and form UI (no dependencies)", "UI chat dan formulir (tanpa dependensi)")],
                ["<code>includes/fpdf/</code>", T("FPDF 1.9 library for PDF generation (its own permissive licence)", "Library FPDF 1.9 untuk membuat PDF (lisensi permisif tersendiri)")],
            ])),

        section("troubleshooting", T("Troubleshooting", "Pemecahan masalah"),
            table([T("Symptom", "Gejala"), T("What to check", "Yang perlu dicek")], [
                [T("The button does not appear", "Tombol tidak muncul"), T("Test mode is on (log in as admin), the assistant is switched off, or a page cache is serving an older page.", "Mode uji aktif (login sebagai admin), asisten dimatikan, atau cache halaman masih menyajikan versi lama.")],
                [T("Chat immediately shows the form", "Chat langsung menampilkan formulir"), T("No API key, provider set to Off, out of credit, or the daily limit was reached. Errors are written to the PHP error log with the prefix “OGNIVID AI Assistant:”.", "API key belum ada, penyedia diatur Off, saldo habis, atau batas harian tercapai. Error dicatat di PHP error log dengan awalan “OGNIVID AI Assistant:”.")],
                [T("Brief saved but no email", "Brief tersimpan tetapi email tidak masuk"), T("Send a test from WP Mail SMTP → Tools. If it succeeds, check spam, forwarders on the recipient mailbox, and how the mailbox is connected to your mail app (IMAP).", "Kirim tes dari WP Mail SMTP → Tools. Jika berhasil, cek folder spam, forwarder di mailbox penerima, dan cara mailbox tersambung ke aplikasi email (IMAP).")],
                [T("PDF missing", "PDF tidak ada"), T("The uploads folder must be writable; the email is still sent without an attachment.", "Folder uploads harus bisa ditulis; email tetap terkirim tanpa lampiran.")],
            ])),

        section("next", T("Roadmap", "Rencana pengembangan"),
            ul([T("WhatsApp notification for new briefs (via a WhatsApp Business API provider).", "Notifikasi WhatsApp untuk brief baru (via penyedia WhatsApp Business API)."),
                T("Streaming replies for faster perceived response.", "Balasan streaming agar respons terasa lebih cepat."),
                T("Reply to a brief and track its status directly from wp-admin.", "Membalas brief dan melacak statusnya langsung dari wp-admin."),
                T("Simple analytics: conversations, completion rate and top services.", "Analitik sederhana: jumlah percakapan, tingkat penyelesaian, dan layanan terpopuler.")])),
    ]


TOC = [("summary", T("Summary", "Ringkasan")), ("how", T("How it works", "Cara kerja")), ("architecture", T("Architecture", "Arsitektur")),
       ("brief", "Brief"), ("features", T("Features", "Fitur")), ("security", T("Security", "Keamanan")), ("install", T("Install", "Instalasi")), ("usage", T("Usage", "Penggunaan")),
       ("settings", T("Settings", "Pengaturan")), ("providers", T("Providers", "Penyedia")), ("api", "API"),
       ("components", T("Components", "Komponen")), ("troubleshooting", T("Troubleshooting", "Masalah")), ("next", "Roadmap")]


# ---------------------------------------------------------------- styles
CSS = """
:root{--bg:#F6F7F5;--surface:#FFFFFF;--surface2:#EEF1EC;--ink:#151A15;--muted:#5C665C;--line:#DDE3DA;
 --navy:#111111;--acc:#2E8A0E;--acc-ink:#23700A;--acc-tint:#E8F6DF;--amber:#E8913A;--amber-tint:#FDF0E3;--hero:#111111;--hero-ink:#C9CDD3;--eyebrow:#7CFF3B;--on-acc:#FFFFFF;
 --shadow:0 1px 2px rgba(17,17,17,.06),0 6px 20px rgba(17,17,17,.06)}
@media (prefers-color-scheme: dark){:root:not([data-theme="light"]){--bg:#0C0E0C;--surface:#151915;--surface2:#1C221C;--ink:#E7ECE5;--muted:#9AA59A;
 --line:#2A332A;--navy:#E7ECE5;--acc:#7CFF3B;--acc-ink:#9BFF6A;--acc-tint:#1E2D16;--amber:#F2A65E;--amber-tint:#3A2A17;--hero:#050605;--on-acc:#0C0E0C;--shadow:none}}
:root[data-theme="dark"]{--bg:#0C0E0C;--surface:#151915;--surface2:#1C221C;--ink:#E7ECE5;--muted:#9AA59A;--line:#2A332A;--navy:#E7ECE5;
 --acc:#7CFF3B;--acc-ink:#9BFF6A;--acc-tint:#1E2D16;--amber:#F2A65E;--amber-tint:#3A2A17;--hero:#050605;--on-acc:#0C0E0C;--shadow:none}
"""
CSS_COMMON = """
*{box-sizing:border-box}html{scroll-behavior:smooth}
body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.6 "Segoe UI",Calibri,Arial,sans-serif}
a{color:var(--acc-ink)}code{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.88em;background:var(--surface2);padding:1px 5px;border-radius:5px}
.wrap{max-width:1120px;margin:0 auto;padding:0 16px}
header.hero{background:var(--hero);color:#fff;padding:56px 0 44px}
.hero .eyebrow{color:var(--eyebrow);font-weight:600;letter-spacing:.06em;text-transform:uppercase;font-size:13px}
.hero h1{font:700 clamp(32px,5vw,52px)/1.1 Cambria,Georgia,serif;margin:10px 0 12px}
.hero p{color:var(--hero-ink);max-width:760px;margin:0;font-size:18px}
.hero .meta{margin-top:18px;color:var(--hero-ink);opacity:.8;font-size:14px}
nav.toc{position:sticky;top:0;z-index:5;background:color-mix(in srgb,var(--bg) 88%,transparent);backdrop-filter:blur(8px);border-bottom:1px solid var(--line)}
nav.toc .wrap{display:flex;gap:6px;overflow-x:auto;padding-top:10px;padding-bottom:10px;align-items:center}
nav.toc a{white-space:nowrap;text-decoration:none;color:var(--muted);font-size:14px;padding:6px 10px;border-radius:999px}
nav.toc a:hover{background:var(--surface2);color:var(--ink)}
nav.toc .spacer{flex:1}
.tgl{border:1px solid var(--line);background:var(--surface);color:var(--ink);border-radius:999px;padding:6px 12px;font-size:13px;cursor:pointer;white-space:nowrap;text-decoration:none}
a.tgl{color:var(--ink)!important}
section{padding:44px 0 8px;scroll-margin-top:56px}
h2{font:700 30px/1.2 Cambria,Georgia,serif;color:var(--navy);margin:0 0 14px}
h3{font-size:18px;margin:0 0 6px;color:var(--ink)}
.lead{font-size:18px;color:var(--muted);max-width:820px}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:22px}
.stat{background:var(--acc-tint);border-radius:14px;padding:18px;text-align:center}
.stat b{display:block;font:700 34px/1.1 Cambria,Georgia,serif;color:var(--acc-ink)}
.stat span{color:var(--muted);font-size:14px}
.grid{display:grid;gap:14px}.g2{grid-template-columns:repeat(2,1fr)}.g3{grid-template-columns:repeat(3,1fr)}
.card{background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:20px;box-shadow:var(--shadow)}
.card p{margin:0;color:var(--muted)}
.ihead{display:flex;gap:12px;align-items:center;margin-bottom:8px}
.ico{width:40px;height:40px;flex:none;padding:9px;border-radius:50%;background:var(--acc);stroke:#fff;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.amber .ico{background:var(--amber)}
figure{margin:18px 0;background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:10px;overflow:auto}
figure svg{display:block;width:100%;height:auto;min-width:720px;font-family:"Segoe UI",Calibri,Arial,sans-serif}
figcaption{color:var(--muted);font-size:14px;text-align:center;padding:6px}
svg .lane rect{fill:var(--surface2);stroke:var(--line)}svg .lt{fill:var(--muted);font-size:13px;font-weight:700;text-anchor:middle;letter-spacing:.04em;text-transform:uppercase}
svg .bx rect{fill:var(--surface);stroke:var(--line);stroke-width:1.5}svg .bx text{fill:var(--ink);font-size:14px;font-weight:600;text-anchor:middle}
svg .bx text.sm{fill:var(--muted);font-size:11.5px;font-weight:400}
svg .bx.hl rect{stroke:var(--acc);stroke-width:2;fill:var(--acc-tint)}svg .bx.amb rect{stroke:var(--amber);stroke-width:2;fill:var(--amber-tint)}
svg .bx.err rect{stroke:#D0505D;stroke-width:1.5;stroke-dasharray:5 4}
svg .ln path{fill:none;stroke:var(--acc);stroke-width:2}svg .mk{fill:var(--acc)}
ul.clean,ol.clean{padding-left:22px;margin:10px 0}ul.clean li,ol.clean li{margin:6px 0}
.note{background:var(--amber-tint);border-radius:12px;padding:14px 16px;color:var(--ink)}
.tbl{overflow-x:auto;border:1px solid var(--line);border-radius:14px;background:var(--surface)}
table{border-collapse:collapse;width:100%;min-width:620px;font-size:15px}
th{background:#111111;color:#fff;text-align:left;padding:10px 14px;font-weight:600}
td{padding:10px 14px;border-top:1px solid var(--line);vertical-align:top}
tbody tr:nth-child(even) td{background:var(--surface2)}
.pill{display:inline-block;background:var(--acc-tint);color:var(--acc-ink);font-weight:700;border-radius:999px;padding:2px 10px;font-size:13px}
.q{font-weight:700;color:var(--navy);margin:0 0 6px!important;font-size:17px}
.starter{display:flex;gap:16px;align-items:flex-start}.starter p{margin:0 0 6px}
.snum{flex:none;width:40px;height:40px;border-radius:50%;background:var(--acc);color:var(--on-acc);font:700 18px/40px Cambria,Georgia,serif;text-align:center}
.snum.amber{background:var(--amber)}
.outcome{color:var(--acc-ink)!important;font-weight:600;margin-top:8px!important}
.uc h3{font-size:19px;margin-bottom:6px}.uc .meta{font-size:14px;margin:0 0 10px}
.ba{display:grid;grid-template-columns:1fr 1fr;gap:12px}.ba div{border-radius:12px;padding:12px 14px;font-size:15px;color:var(--ink)}
.ba span{display:block;font-weight:700;font-size:12px;letter-spacing:.06em;text-transform:uppercase;margin-bottom:4px}
.before{background:var(--surface2)}.before span{color:var(--muted)}.after{background:var(--acc-tint)}.after span{color:var(--acc-ink)}
a.btn{display:inline-block;background:var(--acc);color:var(--on-acc);text-decoration:none;font-weight:600;padding:10px 18px;border-radius:10px}
footer{padding:40px 0 60px;color:var(--muted);font-size:14px;border-top:1px solid var(--line);margin-top:40px}
body.present nav.toc{display:none}
body.present section,body.present header.hero{min-height:100vh;display:flex;flex-direction:column;justify-content:center}
body.present section{border-top:1px solid var(--line)}
@media (max-width:820px){.stats{grid-template-columns:repeat(2,1fr)}.g2,.g3{grid-template-columns:1fr}.ba{grid-template-columns:1fr}h2{font-size:26px}}
@media (min-width:821px){nav.toc .wrap{flex-wrap:wrap;overflow-x:visible;row-gap:4px}}
@media print{nav.toc,.tgl{display:none}section{break-inside:avoid}body{background:#fff}}
"""
JS = """<script>
(function(){var r=document.documentElement,k='doc-theme';
function set(t){if(t)r.setAttribute('data-theme',t);else r.removeAttribute('data-theme');try{localStorage.setItem(k,t)}catch(e){}}
try{var t=localStorage.getItem(k);if(t)r.setAttribute('data-theme',t)}catch(e){}
document.getElementById('theme').onclick=function(){var c=r.getAttribute('data-theme');set(c==='dark'?'light':c==='light'?'':'dark')};
document.getElementById('present').onclick=function(){document.body.classList.toggle('present')};
document.addEventListener('keydown',function(e){if(e.key==='f'&&document.body.classList.contains('present')){document.fullscreenElement?document.exitFullscreen():document.documentElement.requestFullscreen&&document.documentElement.requestFullscreen()}});
})();
</script>"""


def page():
    other = "id" if LANG == "en" else "en"
    other_href = ("../" + LINK["en"]) if LANG == "id" else LINK["id"]
    toc = "".join(f'<a href="#{i}">{s(t)}</a>' for i, t in TOC)
    return f"""<!doctype html>
<html lang="{LANG}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>OGNIVID AI Assistant</title>
<meta name="description" content="{s(T("Documentation for the OGNIVID AI Assistant WordPress plugin: an AI intake assistant that turns website visitors into structured project briefs.", "Dokumentasi plugin WordPress OGNIVID AI Assistant: asisten AI yang mengubah pengunjung website menjadi project brief terstruktur."))}">
<style>{CSS}{CSS_COMMON}</style>
</head>
<body>
<header class="hero"><div class="wrap">
  <div class="eyebrow">{s(T("WordPress plugin · Documentation", "Plugin WordPress · Dokumentasi"))}</div>
  <h1>OGNIVID AI Assistant</h1>
  <p>{s(T("AI intake assistant that interviews website visitors and delivers a structured Project Brief by email and PDF", "Asisten AI yang mewawancarai pengunjung website dan mengirim Project Brief terstruktur lewat email dan PDF"))}</p>
  <div class="meta">{s(T("Version 1.2 · November 2026 · For site owners, developers and integration engineers", "Versi 1.2 · November 2026 · Untuk pemilik situs, developer, dan integration engineer"))}</div>
  <p style="margin-top:18px"><a class="btn" href="{"" if LANG == "en" else "../"}../demo/{"" if LANG == "en" else "?lang=id"}">{s(T("Open the live mockup", "Buka live mockup"))} →</a></p>
</div></header>
<nav class="toc" aria-label="Contents"><div class="wrap">{toc}<span class="spacer"></span>
  <a class="tgl" href="{other_href}" hreflang="{other}">{"Bahasa Indonesia" if LANG == "en" else "English"}</a>
  <button class="tgl" id="present" title="One section per screen (F = full screen)">{s(T("Presentation mode", "Mode presentasi"))}</button>
  <button class="tgl" id="theme">{s(T("Theme", "Tema"))}</button>
</div></nav>
<main class="wrap">
{"".join(content())}
</main>
<footer><div class="wrap">OGNIVID AI Assistant · GPL-2.0-or-later · <a href="{"" if LANG == "en" else "../"}../demo/{"" if LANG == "en" else "?lang=id"}">{s(T("Live mockup", "Live mockup"))}</a> · <a href="{REPO}">GitHub</a> · <a href="{SITE}">ognivid.com</a></div></footer>
{JS}
</body>
</html>
"""


if __name__ == "__main__":
    for LANG in ("en", "id"):
        OUT[LANG].parent.mkdir(parents=True, exist_ok=True)
        OUT[LANG].write_text(page(), encoding="utf-8")
        print("wrote", OUT[LANG].relative_to(ROOT))
