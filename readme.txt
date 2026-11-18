=== OGNIVID AI Assistant ===
Contributors: diimasogan
Tags: ai, chatbot, lead generation, claude, openai
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI intake assistant that interviews your visitors about their project and emails you a structured project brief (HTML + PDF).

== Description ==

OGNIVID AI Assistant adds a chat button to your site. When a visitor opens it, an AI assistant interviews them like an experienced consultant: what they need, which systems are involved, constraints, urgency, timeline, budget and contact details. It then shows the visitor a short summary, and only after they confirm does it send you a structured **Project Brief**.

You receive the brief as an HTML email with a PDF attached, the customer receives a copy, and every brief is stored in a private **AI Briefs** inbox in wp-admin.

**Highlights**

* **Consultative interview**: short replies, one or two focused questions at a time, then a summary the visitor confirms.
* **Structured brief**: customer details, summary, problem, goals, current systems, requirements, constraints, urgency, timeline, budget, relevant services, an initial recommendation and open questions.
* **Email + PDF** for you and a copy for the customer (with an optional WhatsApp follow-up link).
* **AI Briefs inbox** in wp-admin: customer, urgency, source and status columns, unread badge, status filter, full transcript, PDF download, internal notes, one-click reply by email or WhatsApp.
* **Your business, your words**: business name, description, services, extra instructions and greeting are all set in the settings, so the assistant talks about *your* business.
* **9 AI providers**: Anthropic Claude (default), OpenAI, Google Gemini, Mistral, DeepSeek, xAI, Groq, OpenRouter, or any OpenAI-compatible endpoint.
* **Never loses a lead**: if no AI provider is configured, the provider fails, credit runs out or the daily limit is reached, visitors get a short form instead and you still receive the brief.
* **Bilingual widget**: English and Indonesian, following the page language.
* **Safeguards**: conversation history kept on the server, same-origin check, per-conversation, per-visitor and daily limits, honeypot on the form, test mode, and private PDF storage.

The plugin was built for [ognivid.com](https://ognivid.com/) and is free and open source. Source code, documentation and a live mockup: [github.com/diimasogan/ognivid-ai-assistant](https://github.com/diimasogan/ognivid-ai-assistant).

== External services ==

This plugin can connect to **one** third-party AI service that you choose in **Settings → OGNIVID AI Assistant**. Nothing is sent to any AI service until you select a provider **and** enter your own API key. With the provider set to "Off" (or no API key), the plugin only uses the built-in form and makes no external requests.

**What is sent and when:** each time a visitor sends a chat message, the plugin sends the conversation so far (the visitor's messages and the assistant's replies), the system instructions built from your settings (business name, description, services, extra instructions) and the page language to the selected provider's API, so it can generate the next reply. Contact details are sent only if the visitor types them into the chat. No data is sent on page load, and nothing is sent for visitors who never open the chat.

The provider you choose processes this data under its own terms:

* **Anthropic (Claude)**, api.anthropic.com. [Terms](https://www.anthropic.com/legal/commercial-terms), [Privacy policy](https://www.anthropic.com/legal/privacy)
* **OpenAI**, api.openai.com. [Terms](https://openai.com/policies/services-agreement/), [Privacy policy](https://openai.com/policies/privacy-policy/)
* **Google Gemini**, generativelanguage.googleapis.com. [Terms](https://ai.google.dev/gemini-api/terms), [Privacy policy](https://policies.google.com/privacy)
* **Mistral AI**, api.mistral.ai. [Terms and privacy](https://mistral.ai/terms)
* **DeepSeek**, api.deepseek.com. [Terms](https://cdn.deepseek.com/policies/en-US/deepseek-terms-of-use.html), [Privacy policy](https://cdn.deepseek.com/policies/en-US/deepseek-privacy-policy.html)
* **xAI (Grok)**, api.x.ai. [Terms](https://x.ai/legal/terms-of-service), [Privacy policy](https://x.ai/legal/privacy-policy)
* **Groq**, api.groq.com. [Terms](https://groq.com/terms-of-use), [Privacy policy](https://groq.com/privacy-policy)
* **OpenRouter**, openrouter.ai. [Terms](https://openrouter.ai/terms), [Privacy policy](https://openrouter.ai/privacy)
* **Custom endpoint**: any OpenAI-compatible URL you enter yourself; that service's own terms apply.

Briefs are delivered with WordPress's own `wp_mail()` function, through whatever mail setup your site uses.

== Installation ==

1. Install the plugin from **Plugins → Add New Plugin** (search for "OGNIVID AI Assistant"), or upload the ZIP via **Plugins → Add New Plugin → Upload Plugin**.
2. Activate the plugin. A new **AI Briefs** menu appears.
3. Go to **Settings → OGNIVID AI Assistant**. The plugin starts in **test mode**, so only logged-in administrators see the chat button.
4. Fill in **Your business** (name, description, services). This is what the assistant knows about you.
5. Choose an **AI provider**, paste your API key and enter a model ID. Leave the key empty to use the form only.
6. Set **Send briefs to**, make sure your site can send email (for example with an SMTP plugin), and send a test brief.
7. Untick **Test mode** and save. The assistant is now live.

== Frequently Asked Questions ==

= Do I need an AI subscription? =

You need an API key from the provider you choose. API usage is billed by the provider (for example Anthropic or OpenAI) and is separate from consumer subscriptions such as ChatGPT Plus or Claude Pro. Without a key, the plugin works in form-only mode for free.

= Which provider should I use? =

Anthropic Claude is the default and the provider the plugin was designed around. You can switch to OpenAI, Gemini, Mistral, DeepSeek, xAI, Groq, OpenRouter or a custom endpoint at any time.

= Where are briefs stored? =

In your own WordPress database as a private post type (wp-admin → AI Briefs). PDFs are written to a protected folder inside uploads and can only be downloaded by logged-in users who can edit the brief.

= What happens when the AI is unavailable? =

The chat switches to a short form automatically. You still receive the brief by email and in AI Briefs.

= Can visitors abuse it? =

Requests must come from your own site, and you can limit messages per conversation, characters per message, messages per visitor per hour and AI conversations per day.

= Is it translated? =

The chat widget is in English and Indonesian and follows the page language. The admin screens are in English.

== Screenshots ==

1. The chat widget interviewing a visitor.
2. The project brief email the team receives.
3. The AI Briefs inbox in wp-admin.
4. The settings page: business profile and AI provider.

== Changelog ==

= 2.0.0 =
* Business profile settings (name, description, services, extra instructions, greeting) so the plugin works for any business.
* Accent colour setting.
* Unique `ognivid_ai_` prefix for all options, post types and meta (existing 1.x data is migrated automatically).
* `uninstall.php` removes plugin options.
* Built-in PDF writer (no third-party library).
* Defaults use the site name and admin email.

= 1.2.0 =
* New providers: OpenAI, Mistral, DeepSeek and xAI.

= 1.1.0 =
* AI Briefs inbox: status, unread badge, filters, internal notes and reply buttons.

= 1.0.0 =
* First release.

== Upgrade Notice ==

= 2.0.0 =
Adds business profile settings. Existing settings and briefs are migrated automatically.
