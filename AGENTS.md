# Knowledge Graph Integration

This repository has a persistent Graphify knowledge graph generated in `graphify-out/`.

### Guidelines for the Assistant:
1. **Always Check the Graph First**:
   - Whenever answering architectural questions, tracing dependencies, debugging workflows, or locating definitions across `app/` and `public/`, consult `graphify-out/GRAPH_REPORT.md` and use the graph before scanning files manually.
2. **Fast Querying**:
   - Query the knowledge graph using:
     ```bash
     graphify query "<question>"
     ```
   - For finding connections between two concepts:
     ```bash
     graphify path "<NodeA>" "<NodeB>"
     ```
   - For understanding a core abstraction:
     ```bash
     graphify explain "<NodeName>"
     ```
3. **Incremental Updates**:
   - If new files or significant changes are made, run `/graphify --update` to re-extract modified files without rebuilding the full graph.

# Frontend Engineering & UI Standards

Whenever building, modifying, or reviewing any frontend interface, components, or styles:
1. **Aesthetic Direction (`frontend-design`)**: Avoid generic AI defaults, uninspired templates, or cookie-cutter color schemes. Apply deliberate typography, distinct palettes, and purposeful visual identity.
2. **Architecture & Standards (`frontend-ui-engineering`)**: Ensure production-quality component separation, responsive design across viewports, proper state management, and WCAG AA accessibility.
3. **Craft & Polish (`impeccable`)**: Follow design tokens, handle all edge cases (empty states, errors, loading skeletons), and polish micro-interactions to meet strict craft floors.

# Tooling & Command Constraints

### 1. File Inspection & Stream Editing
- **NO IN-PLACE EDITING VIA SHELL:**
  - Never execute `sed -i`, `awk -i`, or pipe stdout to overwrite files (`> file.ext`).
  - All file edits must use native IDE modification tools (`replace_file_content` / diff patches / `edit_file`).
- **FILE PAGINATION / CHUNKING:**
  - Do NOT use `sed -n 'X,Yp'` for reading slices of files.
  - Primary: Use native `read_file(path, offset, limit)`.
  - Fallback: Use `awk 'NR>=start && NR<=end' filename` or `head -n Y filename | tail -n +X`.

### 2. Database Execution & Inspection
- **Whitelisted Read-Only Commands:**
  - You may freely use `execute_sql` for: `SELECT`, `DESCRIBE` / `DESC`, `SHOW`, and `EXPLAIN`.
  - Use dedicated read tools whenever possible: `describe_table`, `show_create_table`, `list_tables`, and `read_query`.
- **Forbidden Raw Mutations:**
  - NEVER run `UPDATE`, `DELETE`, `DROP`, `TRUNCATE`, or `ALTER` via raw `execute_sql`.
  - Any data insertion or schema alteration requires explicit confirmation and dedicated tooling.


