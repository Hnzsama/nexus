<?php
/**
 * Swagger UI & Scalar Modern API Explorer View Template
 * 
 * @var \Core\View\ViewEngine $this
 * @var string $title
 * @var string $specUrl
 */

$this->layout('layouts/main');
?>

<?php $this->block('title'); ?>
<?= $this->e($title) ?> &bull; Nexus Framework
<?php $this->endBlock(); ?>

<?php $this->block('head'); ?>
<!-- Load Swagger UI Base CSS First -->
<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.18.2/swagger-ui.css" />

<!-- Custom High-Priority Dark Theme Overrides for Swagger UI & DaisyUI Integration -->
<style>
    /* Global Container */
    #swagger-ui,
    .swagger-ui {
        color: #f1f5f9 !important;
        font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif !important;
    }

    /* Top Info Block */
    .swagger-ui .info {
        margin: 16px 0 28px !important;
    }
    .swagger-ui .info .title {
        color: #ffffff !important;
        font-size: 2.25rem !important;
        font-weight: 800 !important;
        letter-spacing: -0.03em !important;
        line-height: 1.2 !important;
    }
    .swagger-ui .info .title small {
        background-color: #6366f1 !important;
        color: #ffffff !important;
        border-radius: 9999px !important;
        font-size: 0.75rem !important;
        padding: 4px 12px !important;
        font-weight: 700 !important;
        margin-left: 8px !important;
        vertical-align: middle !important;
        box-shadow: 0 0 12px rgba(99, 102, 241, 0.4) !important;
    }
    .swagger-ui .info .title small.version-stamp {
        background-color: #0284c7 !important;
    }
    .swagger-ui .info p,
    .swagger-ui .info li,
    .swagger-ui .info table {
        color: #94a3b8 !important;
        font-size: 0.95rem !important;
        line-height: 1.6 !important;
    }
    .swagger-ui .info a,
    .swagger-ui .info .base-url {
        color: #38bdf8 !important;
        text-decoration: underline !important;
    }

    /* Servers Box (.scheme-container) */
    .swagger-ui .scheme-container,
    #swagger-ui .scheme-container {
        background: #111827 !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        border-radius: 0.875rem !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4) !important;
        padding: 18px 24px !important;
        margin: 20px 0 32px !important;
    }
    .swagger-ui .scheme-container .servers {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
    }
    .swagger-ui .scheme-container .servers-title,
    .swagger-ui .scheme-container label {
        color: #cbd5e1 !important;
        font-weight: 700 !important;
        font-size: 0.85rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        margin: 0 !important;
    }
    .swagger-ui .scheme-container select,
    #swagger-ui .scheme-container select {
        background-color: #1f2937 !important;
        color: #f9fafb !important;
        border: 1px solid #374151 !important;
        border-radius: 0.5rem !important;
        padding: 9px 16px !important;
        font-size: 0.875rem !important;
        font-weight: 500 !important;
        outline: none !important;
        cursor: pointer !important;
        transition: border-color 0.2s, box-shadow 0.2s !important;
    }
    .swagger-ui .scheme-container select:focus {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25) !important;
    }

    /* Authorize Button & Modal Dialog */
    .swagger-ui .auth-wrapper {
        padding-bottom: 12px !important;
        display: flex !important;
        justify-content: flex-end !important;
    }
    .swagger-ui .btn.authorize {
        color: #34d399 !important;
        border: 1px solid rgba(52, 211, 153, 0.4) !important;
        background: rgba(16, 185, 129, 0.1) !important;
        border-radius: 0.5rem !important;
        padding: 8px 18px !important;
        font-weight: 700 !important;
        font-size: 0.875rem !important;
        transition: all 0.2s ease !important;
    }
    .swagger-ui .btn.authorize:hover {
        background: #10b981 !important;
        color: #ffffff !important;
        box-shadow: 0 0 16px rgba(16, 185, 129, 0.35) !important;
    }
    .swagger-ui .btn.authorize svg {
        fill: currentColor !important;
    }
    .swagger-ui .dialog-ux .backdrop-ux {
        background: rgba(3, 7, 18, 0.82) !important;
        backdrop-filter: blur(8px) !important;
    }
    .swagger-ui .dialog-ux .modal-ux {
        background: #0f172a !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        border-radius: 1rem !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6) !important;
        color: #f1f5f9 !important;
    }
    .swagger-ui .dialog-ux .modal-ux-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        padding: 16px 20px !important;
    }
    .swagger-ui .dialog-ux .modal-ux-header h3 {
        color: #ffffff !important;
        font-weight: 700 !important;
    }
    .swagger-ui .dialog-ux .modal-ux-content {
        padding: 20px !important;
    }
    .swagger-ui .dialog-ux .modal-ux-content h4 {
        color: #f8fafc !important;
    }
    .swagger-ui .dialog-ux .modal-ux-content p {
        color: #94a3b8 !important;
    }

    /* Section & Tag Titles */
    .swagger-ui .opblock-tag,
    #swagger-ui .opblock-tag {
        color: #ffffff !important;
        font-size: 1.35rem !important;
        font-weight: 700 !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        padding: 16px 0 12px !important;
        margin: 28px 0 16px !important;
    }
    .swagger-ui .opblock-tag small {
        color: #94a3b8 !important;
        font-size: 0.875rem !important;
        font-weight: 400 !important;
        padding-left: 12px !important;
    }
    .swagger-ui .opblock-tag:hover {
        color: #38bdf8 !important;
    }

    /* Operation Endpoint Cards */
    .swagger-ui .opblock {
        border-radius: 0.75rem !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
        margin-bottom: 14px !important;
        border-width: 1px !important;
        border-style: solid !important;
        overflow: hidden !important;
    }
    .swagger-ui .opblock .opblock-summary {
        padding: 12px 18px !important;
        display: flex !important;
        align-items: center !important;
    }
    .swagger-ui .opblock .opblock-summary-path {
        color: #f1f5f9 !important;
        font-family: 'JetBrains Mono', monospace !important;
        font-size: 0.95rem !important;
        font-weight: 600 !important;
    }
    .swagger-ui .opblock .opblock-summary-description {
        color: #94a3b8 !important;
        font-size: 0.875rem !important;
    }

    /* Method Summary Badges & Card Tint */
    .swagger-ui .opblock.opblock-get {
        background: rgba(14, 165, 233, 0.06) !important;
        border-color: rgba(56, 189, 248, 0.25) !important;
    }
    .swagger-ui .opblock.opblock-get .opblock-summary-method {
        background: #0284c7 !important;
        color: #ffffff !important;
        font-weight: 800 !important;
        border-radius: 0.375rem !important;
        min-width: 70px !important;
        text-align: center !important;
        font-family: monospace !important;
        box-shadow: 0 0 10px rgba(2, 132, 199, 0.4) !important;
    }

    .swagger-ui .opblock.opblock-post {
        background: rgba(16, 185, 129, 0.06) !important;
        border-color: rgba(52, 211, 153, 0.25) !important;
    }
    .swagger-ui .opblock.opblock-post .opblock-summary-method {
        background: #10b981 !important;
        color: #ffffff !important;
        font-weight: 800 !important;
        border-radius: 0.375rem !important;
        min-width: 70px !important;
        text-align: center !important;
        font-family: monospace !important;
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.4) !important;
    }

    .swagger-ui .opblock.opblock-put {
        background: rgba(245, 158, 11, 0.06) !important;
        border-color: rgba(251, 191, 36, 0.25) !important;
    }
    .swagger-ui .opblock.opblock-put .opblock-summary-method {
        background: #f59e0b !important;
        color: #ffffff !important;
        font-weight: 800 !important;
        border-radius: 0.375rem !important;
        min-width: 70px !important;
        text-align: center !important;
        font-family: monospace !important;
        box-shadow: 0 0 10px rgba(245, 158, 11, 0.4) !important;
    }

    .swagger-ui .opblock.opblock-delete {
        background: rgba(239, 68, 68, 0.06) !important;
        border-color: rgba(248, 113, 113, 0.25) !important;
    }
    .swagger-ui .opblock.opblock-delete .opblock-summary-method {
        background: #ef4444 !important;
        color: #ffffff !important;
        font-weight: 800 !important;
        border-radius: 0.375rem !important;
        min-width: 70px !important;
        text-align: center !important;
        font-family: monospace !important;
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.4) !important;
    }

    /* Operation Body (Expanded content) */
    .swagger-ui .opblock-body {
        background: #090e1a !important;
        color: #ffffff !important;
        border-top: 1px solid rgba(255, 255, 255, 0.08) !important;
        padding: 22px !important;
    }

    /* Form Parameters & Request Body Fields (Bright Legible White Text on Dark Mode) */
    .swagger-ui .opblock-description-wrapper,
    .swagger-ui .opblock-description-wrapper p,
    .swagger-ui .opblock-title_normal,
    .swagger-ui .opblock-title_normal p {
        color: #ffffff !important;
        font-size: 0.95rem !important;
        font-weight: 500 !important;
        line-height: 1.6 !important;
    }

    .swagger-ui .parameter__name {
        color: #ffffff !important;
        font-weight: 700 !important;
        font-size: 0.95rem !important;
        letter-spacing: -0.01em !important;
    }

    .swagger-ui .parameter__name.required:after {
        color: #ef4444 !important;
        font-weight: 800 !important;
    }

    .swagger-ui .parameter__name.required span {
        color: #f87171 !important;
        font-weight: 700 !important;
        font-size: 0.8rem !important;
        margin-left: 6px !important;
    }

    .swagger-ui .parameter__type {
        color: #38bdf8 !important;
        font-family: 'JetBrains Mono', Consolas, monospace !important;
        font-size: 0.85rem !important;
        font-weight: 600 !important;
    }

    .swagger-ui .parameter__extension,
    .swagger-ui .parameter__in {
        color: #94a3b8 !important;
        font-size: 0.8rem !important;
        font-family: monospace !important;
    }

    .swagger-ui .parameters-col_description,
    .swagger-ui .parameters-col_description p,
    .swagger-ui .renderedMarkdown p,
    .swagger-ui .markdown p,
    .swagger-ui .prop-desc,
    .swagger-ui .prop-desc p {
        color: #ffffff !important;
        font-size: 0.95rem !important;
        font-weight: 500 !important;
        line-height: 1.6 !important;
    }

    .swagger-ui table.parameters tbody tr td {
        color: #ffffff !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        vertical-align: top !important;
        padding: 14px 12px !important;
    }

    .swagger-ui .parameters-container .parameters-col_name {
        color: #ffffff !important;
    }

    .swagger-ui .property,
    .swagger-ui .prop-name {
        color: #ffffff !important;
        font-weight: 700 !important;
        font-size: 0.9rem !important;
    }

    .swagger-ui .prop-type {
        color: #38bdf8 !important;
        font-weight: 600 !important;
    }

    .swagger-ui .prop-format {
        color: #a78bfa !important;
    }

    .swagger-ui .body-param-options,
    .swagger-ui .body-param-options label {
        color: #f1f5f9 !important;
        font-weight: 500 !important;
    }

    /* FIX: Ugly Light Gray Section Headers (Parameters, Request Body, Responses) */
    .swagger-ui .opblock-section-header,
    #swagger-ui .opblock-section-header {
        background: #111827 !important;
        border: 1px solid rgba(255, 255, 255, 0.09) !important;
        border-radius: 0.625rem !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3) !important;
        padding: 10px 18px !important;
        margin-bottom: 16px !important;
        min-height: auto !important;
        align-items: center !important;
    }
    .swagger-ui .opblock-section-header h4 {
        color: #f1f5f9 !important;
        font-size: 0.875rem !important;
        font-weight: 700 !important;
        letter-spacing: -0.01em !important;
        margin: 0 !important;
    }
    .swagger-ui .opblock-section-header label {
        color: #94a3b8 !important;
        font-size: 0.8rem !important;
    }
    .swagger-ui .opblock-section-header .opblock-section-request-body {
        color: #f1f5f9 !important;
        font-weight: 600 !important;
        font-size: 0.875rem !important;
    }
    .swagger-ui .opblock-section-header .opblock-section-request-body .required {
        color: #f87171 !important;
        font-weight: 700 !important;
        margin-left: 6px !important;
    }

    /* Try It Out Button */
    .swagger-ui .btn.try-out__btn {
        border: 1px solid rgba(99, 102, 241, 0.45) !important;
        color: #c7d2fe !important;
        background: rgba(99, 102, 241, 0.12) !important;
        border-radius: 0.5rem !important;
        padding: 6px 14px !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        transition: all 0.2s ease !important;
    }
    .swagger-ui .btn.try-out__btn:hover {
        background: #4f46e5 !important;
        color: #ffffff !important;
        border-color: #6366f1 !important;
        box-shadow: 0 0 12px rgba(99, 102, 241, 0.35) !important;
    }
    .swagger-ui .btn.try-out__btn.cancel {
        border-color: rgba(239, 68, 68, 0.45) !important;
        color: #fca5a5 !important;
        background: rgba(239, 68, 68, 0.12) !important;
    }
    .swagger-ui .btn.try-out__btn.cancel:hover {
        background: #dc2626 !important;
        color: #ffffff !important;
    }

    /* Execute Button */
    .swagger-ui .btn.execute {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%) !important;
        color: #ffffff !important;
        border: none !important;
        box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4) !important;
        border-radius: 0.5rem !important;
        padding: 8px 24px !important;
        font-weight: 700 !important;
        font-size: 0.875rem !important;
        transition: transform 0.15s, box-shadow 0.15s !important;
    }
    .swagger-ui .btn.execute:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 6px 20px rgba(79, 70, 229, 0.55) !important;
    }

    /* Tabs (Example Value / Schema) */
    .swagger-ui .tab {
        color: #94a3b8 !important;
        font-size: 0.8rem !important;
        font-weight: 600 !important;
        padding: 6px 14px !important;
        transition: color 0.15s !important;
    }
    .swagger-ui .tab.active {
        color: #38bdf8 !important;
        border-bottom: 2px solid #38bdf8 !important;
    }
    .swagger-ui .tab:hover {
        color: #f1f5f9 !important;
    }
    .swagger-ui .tab-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
        margin-bottom: 10px !important;
    }

    /* Responses Table & Elements */
    .swagger-ui .responses-table {
        background: transparent !important;
    }
    .swagger-ui .responses-table thead tr th {
        color: #94a3b8 !important;
        font-size: 0.75rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        font-weight: 700 !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12) !important;
        padding: 10px 14px !important;
    }
    .swagger-ui .responses-table thead tr td {
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    }
    .swagger-ui .responses-table .response-col_status {
        color: #38bdf8 !important;
        font-family: 'JetBrains Mono', monospace !important;
        font-weight: 700 !important;
        font-size: 0.95rem !important;
        padding: 12px 14px !important;
    }
    .swagger-ui .responses-table .response-col_description {
        color: #e2e8f0 !important;
        padding: 12px 14px !important;
    }
    .swagger-ui .responses-table .response-col_links {
        color: #64748b !important;
        padding: 12px 14px !important;
    }

    /* Media Type Labels & Content-Type Dropdowns */
    .swagger-ui .response-control-media-type__title,
    .swagger-ui .body-param-content-type label {
        color: #94a3b8 !important;
        font-size: 0.75rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.04em !important;
        font-weight: 600 !important;
        margin-bottom: 4px !important;
    }
    .swagger-ui .response-control-media-type__accept-message {
        color: #64748b !important;
        font-size: 0.75rem !important;
    }
    .swagger-ui select,
    .swagger-ui .content-type-wrapper select,
    .swagger-ui .parameters-container select {
        background-color: #1e293b !important;
        color: #f8fafc !important;
        border: 1px solid #334155 !important;
        border-radius: 0.5rem !important;
        padding: 7px 14px !important;
        font-size: 0.85rem !important;
        font-weight: 500 !important;
        box-shadow: none !important;
    }
    .swagger-ui select:focus {
        border-color: #6366f1 !important;
        outline: none !important;
    }

    /* Inputs & Textareas */
    .swagger-ui input,
    .swagger-ui input[type],
    .swagger-ui input[type=text],
    .swagger-ui input[type=password],
    .swagger-ui input[type=email],
    .swagger-ui input[type=tel],
    .swagger-ui input[type=number],
    .swagger-ui textarea {
        background-color: #1e293b !important;
        background: #1e293b !important;
        color: #ffffff !important;
        -webkit-text-fill-color: #ffffff !important;
        border: 1px solid #334155 !important;
        border-radius: 0.5rem !important;
        padding: 9px 14px !important;
        font-size: 0.875rem !important;
        font-family: inherit !important;
        box-shadow: none !important;
        transition: border-color 0.2s, box-shadow 0.2s !important;
    }
    .swagger-ui input::placeholder,
    .swagger-ui textarea::placeholder {
        color: #64748b !important;
        opacity: 1 !important;
    }
    .swagger-ui input:focus,
    .swagger-ui input[type]:focus,
    .swagger-ui input[type=text]:focus,
    .swagger-ui input[type=password]:focus,
    .swagger-ui input[type=email]:focus,
    .swagger-ui textarea:focus {
        border-color: #6366f1 !important;
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25) !important;
        background-color: #1e293b !important;
        background: #1e293b !important;
        color: #ffffff !important;
    }
    .swagger-ui input:-webkit-autofill,
    .swagger-ui input:-webkit-autofill:hover,
    .swagger-ui input:-webkit-autofill:focus {
        -webkit-text-fill-color: #ffffff !important;
        -webkit-box-shadow: 0 0 0px 1000px #1e293b inset !important;
        box-shadow: 0 0 0px 1000px #1e293b inset !important;
        transition: background-color 5000s ease-in-out 0s;
    }

    /* Code Blocks & Preformatted JSON */
    .swagger-ui .highlight-code,
    .swagger-ui pre,
    .swagger-ui .microlight,
    .swagger-ui .body-param__example {
        background: #030712 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.625rem !important;
        color: #e2e8f0 !important;
        font-family: 'JetBrains Mono', Consolas, monospace !important;
        font-size: 0.825rem !important;
        line-height: 1.55 !important;
        padding: 14px 18px !important;
    }

    /* Models Section */
    .swagger-ui section.models {
        background: #111827 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 0.75rem !important;
        margin-top: 32px !important;
    }
    .swagger-ui section.models h4 {
        color: #ffffff !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }
    .swagger-ui section.models .model-container {
        background: #090e1a !important;
        border: 1px solid rgba(255, 255, 255, 0.06) !important;
        border-radius: 0.5rem !important;
        margin: 10px 0 !important;
    }
    .swagger-ui .model-title {
        color: #38bdf8 !important;
    }
    .swagger-ui .prop-type {
        color: #a78bfa !important;
    }

    /* Scalar Embed Wrapper */
    #view-scalar {
        border-radius: 1rem;
        overflow: hidden;
    }
</style>
<?php $this->endBlock(); ?>

<div class="space-y-6">
    <!-- Top DaisyUI Card Header & Controls -->
    <div class="card bg-base-200/70 border border-base-content/10 shadow-2xl overflow-hidden p-6 sm:p-8 relative">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="badge badge-primary gap-1.5 font-mono text-xs py-2.5 px-3">
                        <i data-lucide="file-code" class="w-3.5 h-3.5"></i>
                        <span>OpenAPI 3.0</span>
                    </span>
                    <span class="badge badge-accent badge-soft font-mono text-xs py-2.5 px-3">
                        Dual UI Engine
                    </span>
                    <span class="badge badge-success badge-soft font-mono text-xs py-2.5 px-3">
                        Dynamic Scanner Active
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-100 flex items-center gap-2.5">
                    <span>Nexus RESTful API Explorer</span>
                </h1>
                <p class="text-base-content/70 text-sm max-w-2xl leading-relaxed">
                    Dokumentasi API interaktif yang digenerate otomatis dari PHP 8 Attributes. Pilih antara tampilan <b>Swagger UI Pro Dark</b> atau tampilan ultra-modern <b>Scalar Studio</b>.
                </p>
            </div>

            <!-- Action Buttons with DaisyUI -->
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="<?= route('swagger.scalar') ?>" target="_blank" class="btn btn-outline btn-accent btn-sm gap-2">
                    <i data-lucide="sparkles" class="w-4 h-4 text-accent"></i>
                    <span>Scalar Fullscreen</span>
                    <i data-lucide="external-link" class="w-3.5 h-3.5 opacity-60"></i>
                </a>
                <a href="<?= route('swagger.json') ?>" target="_blank" class="btn btn-outline btn-sm gap-2">
                    <i data-lucide="download" class="w-4 h-4 text-primary"></i>
                    <span>OpenAPI JSON</span>
                </a>
                <a href="<?= route('api.ping') ?>" target="_blank" class="btn btn-ghost btn-sm gap-1.5 text-base-content/80">
                    <i data-lucide="activity" class="w-4 h-4 text-success"></i>
                    <span>Ping Health</span>
                </a>
                <a href="<?= route('docs.index') ?>" class="btn btn-primary btn-sm gap-2 shadow-md shadow-primary/20">
                    <i data-lucide="book-open" class="w-4 h-4"></i>
                    <span>Dev Docs</span>
                </a>
            </div>
        </div>

        <!-- UI Engine Switcher with DaisyUI Tabs -->
        <div class="mt-6 pt-5 border-t border-base-content/10 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-base-content/60">Pilih Tampilan:</span>
                <div role="tablist" class="tabs tabs-box bg-base-300 p-1 rounded-xl">
                    <button id="tab-swagger-btn" role="tab" class="tab tab-active font-bold text-xs gap-2 transition-all">
                        <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
                        <span>Swagger UI (Dark Pro)</span>
                    </button>
                    <button id="tab-scalar-btn" role="tab" class="tab text-xs gap-2 transition-all">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-accent"></i>
                        <span>Scalar Studio (Next-Gen)</span>
                        <span class="badge badge-accent badge-xs font-mono">Modern</span>
                    </button>
                </div>
            </div>

            <div class="text-xs text-base-content/50 font-mono hidden sm:block">
                Endpoint: <span class="text-primary font-semibold"><?= $this->e($specUrl) ?></span>
            </div>
        </div>

        <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-primary/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- View 1: Swagger UI Pro Dark Container -->
    <div id="view-swagger" class="card bg-base-200/50 border border-base-content/10 shadow-2xl p-4 sm:p-8 rounded-2xl overflow-hidden min-h-[600px]">
        <div id="swagger-ui" data-spec-url="<?= $this->e($specUrl) ?>" class="w-full">
            <div class="flex flex-col items-center justify-center py-24 gap-4 text-center">
                <span class="loading loading-spinner loading-lg text-primary"></span>
                <div class="space-y-1">
                    <p class="font-semibold text-sm text-slate-200">Memuat Antarmuka Swagger UI...</p>
                    <p class="text-xs text-base-content/60">Mengambil spesifikasi OpenAPI dari <code class="font-mono text-primary"><?= $this->e($specUrl) ?></code></p>
                </div>
            </div>
        </div>
    </div>

    <!-- View 2: Scalar Modern Studio Container (Hidden by default) -->
    <div id="view-scalar" class="card bg-base-200/50 border border-base-content/10 shadow-2xl p-2 sm:p-4 rounded-2xl overflow-hidden min-h-[750px] hidden">
        <div id="scalar-container" class="w-full min-h-[700px]"></div>
    </div>
</div>
