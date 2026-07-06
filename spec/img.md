Read the entire codebase structure: models, migrations, config/filesystems.php,
existing upload logic, and all modules that handle file/image uploads.

Then design an optimal multi-tenant image storage solution for this Laravel 13 system:
- Multiple organizations, each isolated
- Many modules (courses, SOP, knowledge, recruitment, editor content...)
- Jodit editor v4 needs an upload endpoint
- Currently using: [local / S3 — điền vào]

Analyze and propose:
1. Folder structure convention (org-scoped, module-scoped, model-scoped)
2. Single media_files table schema covering all modules polymorphically
3. File processing pipeline: resize, convert to WebP, generate variants (thumb/medium/full)
4. Upload service architecture reusable across all modules
5. URL resolution strategy: how to serve files without hardcoding CDN domain in DB
6. Jodit-specific endpoint and response format
7. Migration path if storage provider changes (local → S3 → CDN)

Base decisions on what already exists in the codebase. Flag any conflicts or redundancies.
Do not write code yet — output the architecture decision record (ADR) only.