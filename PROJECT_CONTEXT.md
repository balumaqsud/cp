this md is in order to track the process and plan, not to generate code with ai!

discord: # Course Project Use this (depending of what group you are in): **.NET**: C# (required), Blazor or MVC (you choose); of course, use JavaScript or TypeScript as needed; SQL Server/MySQL/PostgreSQL or **PHP**: PHP, Symfony; of course, use JavaScript or TypeScript as needed; MySQL/PostgreSQL Use CSS framework (Bootstrap is recommended, but you can use _any_).
You can use any other libraries, components or even frameworks (but not replace specified above).
There are no limitations in the area of architecture or used services. For example, you are not required to have a separate full-featured application server separated from Web server, or have a lot of things on the client, or use microservices, etc.; you may approach this whatever way you want. It's recommended to use _the simplest and the safest_ approach to the persistence, namely relational databse, e.g. PostgreSQL, MySQL, SQL Server. Again, you may replace Bootstrap with any CSS framework and/or UI library you like. # Idea You have to implement _a Web application for CV management system_ (different positions, experience, skills, etc.). Users define "_positions_" with the set of attributes. Position is a kind of template for CVs; other users fill their CVs for the related positions with specific values. Three of the most important features are customisable positions with arbitrary attributes, reusable library of attributes, and automatic CV generation. E.g., a Recruiter creates a "Business Analyst" positions, adds the "English Level" dropdown attribute and the number-valued field "GPA". Some candidates fill out English levels and GPAs in their profiles and generate CVs tailored for the given positon. Then recruiters browse the CVs to find appropriate candidate. # Warning If your app have N buttons (view/edit/delete in _each_ record), your result will be graded -20%. Use toolbars, or animated "appearing" context actions, etc. This is forbidden:
┌─────────────┬─────────────────┬ ┬────────────────┐  
│ Name │ Position │…│ │  
├─────────────┼─────────────────┼ ┼────────────────┤  
│ Smith, John │ Data Scientist │…│ [Edit][Delete] │
├─────────────┼─────────────────┼ ┼────────────────┤  
│ King, Paul │ DevOps Engineer │…│ [Edit][Delete] │
├─────────────┼─────────────────┼ ┼────────────────┤  
 …  
├─────────────┼─────────────────┼ ┼────────────────┤
│ Morris, Lee │ QA Engineer │…│ [Edit][Delete] │  
└─────────────┴─────────────────┴ ┴────────────────┘
This is OK:
[Delete]

⍰ 𝗡𝗮𝗺𝗲 v 𝗣𝗼𝘀𝗶𝘁𝗶𝗼𝗻   𝗟𝗲𝘃𝗲𝗹  
─────────────────────────────────────────────  
 ☐ King, Paul DevOps Engineer Junior

☑ Morris, Lee QA Engineer Senior

☐ Smith, John D͟a͟t͟a͟ ͟S͟c͟i͟e͟n͟t͟i͟s͟t͟ Middle
☝
Positions, as well as CVs, should be displayed in the table views. The usage "tile" / "gallery" representation will be graded -20%. This is wrong:
┌────────────────┐ ┌────────────────┐  
│ 👤 Morris, Lee    │ │ 👤 Smith, John     │
│ [𝗩𝗶𝗲𝘄]      │ │ [𝗩𝗶𝗲𝘄]       │
└────────────────┘ └────────────────┘
┌────────────────┐  
│ 👤 King, Paul    │
│ [𝗩𝗶𝗲𝘄]      │
└────────────────┘

> You have to use table representation for positions and CVs, not ~~gallery~~ or ~~tiles~~. Every page provides access to full-text search via the top header. > Implement a convenient consistent navigation int your app. # Overview The system is a web-based recruitment platform that allows Candidates to maintain reusable professional profiles and generate tailored CVs for positions managed by Recruiters. The platform is built around three "killer-featues": _ Reusable Attribute Library – attributes can be defined once and reused across multiple positions and CVs; _ Customizable Position Templates – Recruiters can build position-specific CV templates using attributes from the library; _ Automatically Generated CVs – CVs are assembled dynamically from candidate profile data and position requirements. The platform supports three user roles: _ Candidate; _ Recruiter; _ Administrator. # Authentication Non-authenticated users may: _ Register an account; _ Sign in; _ Browse available positions in read-only mode; _ View public statistics (for example, "10 new CVs created in the last 24 hours"). Non-authenticated users may not: create or edit positions, browse or create CVs, post comment or like CVs, access personal pages. Users can authenticate using social login providers (at minimum, the platform must support two). The good options are Google and Facebook, but you may choose others. # Roles Candidates can: _ Manage their personal profile; _ Select and fill attributes from the attribute library; _ Manage project descriptions; _ View positions available to them; _ Create and edit CVs for positions they are allowed to access (if Candidates loose the access, the filled out CVs are hidden); _ Participate in discussions. Candidates may access only their own profile and CVs. All Recruiters share responsibility for the same set of positions. Any Recruiter can modify any position, there is no concept of position ownership. Also, all Recruiters manage the shared pool of attributes. Any Recruiter can: _ Create positions; _ Duplicate existing positions; _ Edit positions; _ Delete positions; _ Configure access rules; _ Manage position templates; _ Manage the attribute library; _ View candidate CVs in read-only mode (using full-text search, accessing CVs through the position or through the personal pages); _ Participate in discussions; _ Like CVs. Administrators have unrestricted access. Admins have full access and can view all pages as if they were the owner. For example, an Admin can open a candidate's page, fix typos, edit attributes, or make any other changes—effectively acting as the owner of every personal page. Administrators can: _ View all pages; _ Edit any candidate profile; _ Edit any CV; _ Edit any position; _ Perform all Recruiter actions; _ Perform all Candidate actions; _ Manage users (viewing, blocking, unblocking, deleting, assigning roles, removing roles). > Administrators may remove their own Administrator role. # Personal Profile Each authenticated user has a personal profile page. Only the profile owner and Administrators may edit or view the full profile. Recruiters cannot access profile editing and only see CV data as read-only page. The profile consists of four sections: ## Me Contains mandatory built-in attributes. These attributes always exist and cannot be removed. E.g., First Name, Last Name, Location, Personal Photo. These attributes should be build on the same "engine" (e.g., can be added to position template), but cannot be removed by Recruiters. ## Info Contains user-selected attributes from the Attribute Library. Candidates may: _ Add or remove attributes from the library; _ Fill values for those attributes. E.g., IELTS Score, Presentation Skills, Remote Work Availability. ## Projects Candidates may maintain a list of projects. Each project contains: _ Name _ Period (date range). _ Description (with Markdown formatting). _ Technology Tags (support autocompletion for the tags prevously entered; use a nice UI component for the tags). Projects can be added, edited, and removed. ## CVs Displays all CVs created by the Candidate. A Candidate may have at most one CV per position. Existing CVs can be edited or deleted. New CVs may be created only for positions accessible to the Candidate. Each CV entry acts as a link to the corresponding CV page. # Auto-Save Personal profile pages support automatic saving: _ Changes are tracked locally; _ Changes are saved every 5–10 seconds; _ Saving must not occur on every keystroke; _ The auto-save mechanism must use optimistic locking. The system must use optimistic locking. Each save operation (for attributes, positions and auto-saves of profiles): _ Sends the current version number; _ Updates the record if the version matches; _ Returns a new version number; _ Fails if the version has changed. The client must handle version conflicts gracefully. # Killer Feature #1: Attribute Library The Attribute Library enables reusable structured data across profiles, positions, and CVs. All Recruiters can: create attributes, edit attributes, delete attributes. Each attribute contains: _ Category—one from a predefined list; _ Name—globally unique attribute name; _ Some kind of description; _ Attribute data type. Examples of categories: Certification, Domain Knowledge, Personal Information, Soft Skills. Supported Attribute Types: _ String (single-line plain text); _ Text (Markdown-formatted text); _ Image (an external cloud storage service by drag-n-drop); _ Numeric; _ Date; _ Period (date range); _ Boolean (checkbox); _ One of many (dropdown). Because the library may become large, attribute selection must support: _ Lookup by prefix; _ Recently used attributes; _ Category filtering. # Killer Feature #2: Positions Positions serve as customizable CV templates. All Recruiters manage a shared list of positions. A Recruiter may: _ Create a blank position or duplicate an existing position; _ Edit any position; _ Delete any position. Each position contains: _ Basic information, incl. Title and Short Description; _ Access Rules (a position may be either public (accessible to all authenticated users) or restricted using filters); _ Attributes selected from the Attribute Library; _ Project tags (for selecting relevant projects) as well maximum number of projects included in the generated CV. Examples of access rules: _ "IELTS Score" numeric value is > 7.0; _ "Remote Work" checkbox is checked; _ "Presentation Skills" value in dropdown = "Advanced". Available filter operators depend on attribute type. Each position provide the list of the CVs created from that position (accessible by Recruits and Administrators only). Candidates may create CVs only for positions they are authorized to access. If Candidate looses the access, the already created CVs aren't deleted, they are hidden in UI. It would be nice to have some kind of additional position attributes like "Company", "Level" (Junior/Middle/Senior/C-level), etc., to filter/sort positions in the tables, however it's not crucial. # Killer Feature #3: CV Generation CVs are generated automatically from: _ Candidate profile data (undeletable attributes like name); _ Selected library attributes (values are either fetched automatically from the attributes set by Candidate); _ Candidate projects (filtered). When user edits an attribute in a CS, the attribute is added to the profile (if necessary; some may be already filled out from the profile); attributes not presented in profile are empty by default. The generated CV should: _ Be professionally formatted; _ Be divided into clearly structured sections; _ Display only relevant information (attributes specified in the position and filtered projects). When a Candidate opens own CV: _ Attributes are pre-filled automatically from the profile; _ Missing information can be entered manually in-place. > Each attribute in the CV can be edited in place (the only one common master value for attirbute is stored in the profile). If value is empty it's highlighted in red color. Editing a attribute in a CV _modified the original profile value_. Recruiters can only view CVs in rendered read-only mode; the empty values are highlighed in red. They cannot directly modify candidate CVs (Administrators can). > It's necessary to have track states for CVs, for example, by having a separate "Publish" action available only if all the attributes are filled out. Thus action will make CV visible by Recruiters. # Discussions Each position contains a Discussion tab. Discussion posts include: author name, timestamp, text content (Markdown-formatted). If page is viewed by Recruiters, the author name links to the user's public profile view. Posts are displayed in chronological order. New posts are always appended to the end, posts cannot be inserted between existing posts. Updates should appear for all active viewers within 2–5 seconds (implementation may use WebSockets, Polling or whatever), # Likes Each CV supports likes. Only Recruiters may like CVs. One Recruiter may give at most one like to a specific CV. A Recruiter may remove their like. The total number of likes is displayed in the CV lists and inn search results. # Main Page The main page contains: _ Latest Positions (table showing the most recently created or updated positions); _ Most Popular Positions (top 5 positions ranked by the number of submitted CVs); _ Tag Cloud with technology tags (linked to CVs for Recruiters or positions for Candidates); _ Statistics (e.g., number if CVs created in the last 24 hours, total number of positions, total number of Candidates, total number of Recruiters, total number of submitted CVs). # More The application should support two UI languages: English and one additional language (e.g., Polish, Spanish, Uzbek, Georgian, etc.). The user selects the language, and their choice is saved. _Only the UI is translated_ — user-generated content such as project descriptions or names is not translated. The application should also support two visual themes: light and dark. The user selects the theme, and their choice is saved. **_Requirements_**: _ Use a CSS framework (e.g., Bootstrap — or any other framework and set of UI controls you prefer); _ Support responsive design for various screen sizes and resolutions, including mobile phones; _ Use an ORM (e.g., Sequelize, Prisma, TypeORM, Entity Framework — any is acceptable); _ Use a full-text search engine, either through an external library or native database features. ***DON'T***s: _ Don’t perform full database scans using raw SELECT _ queries; _ Don’t upload images to your web server or database; _ Don’t execute database queries inside loops; \* Don't add buttons in the table rows. > Is it possible to use the X library? **_Yes_**, yes to all — remember my choice. # Optional Requirements For a separate grade—only if all the core requirements are fully implemented: _ Generate printable documents in PDF formats with QR codes linked back to the app; _ Implement form authentication with email confirmation as an alternative to social network authentication; _ Implement the system of "badges" or "achievements" (like "10 projects", or "5 CVs", or "25 likes"), generate a nice SVG panel on the profile page and allow to download it; _ Add additional "tuning" options to the fields, such as: text length limits, regex validators, value ranges for numeric fields, etc. \* Export from the CVs for the given position to an aggregate CSV/Excel file for analysis. **IMPORTANT NOTE** Don't hope that AI will understand all the requirements perfectly. **_Anyway, it's much better to do less, but fully understand what you write._** I'm dead serious; we will ask you to modify your code on the fly, add or change functionality, as well as ask technical questions. We want not only to check your ability to navigate and work with your own code, but to undestand _why_ every line is written. This is more important than the number of features you implement. Do not copy. **_Use libraries as much as possible_**—but don't copy-paste code. > Use ready-made components, libraries, and controls. For example, use a component to render Markdown, use an image uploader with drag-and-drop support, use a tag input control, use a tag cloud renderer, etc. > The less custom code your app contains, the better. And the most important: **_start by deploying a static "Hello, world" page and always keep a deployable version ready!_** And even more important than that: \***\*defend your project—even if you’ve only completed a small part of it\*\***. > The project submission date is 23.09.2026 (you submit project to HRs, not to me).

summary: # Course Project — CV Management System

## 1. Project Purpose

This project is a web-based CV management and recruitment platform.

The system allows:

- Candidates to maintain reusable professional profiles.
- Recruiters to define customizable job positions.
- Recruiters to create position-specific CV templates using reusable attributes.
- Candidates to generate CVs tailored to specific positions.
- Recruiters to browse and search candidate CVs.
- Candidates and Recruiters to participate in discussions.
- Recruiters to like CVs.
- Administrators to manage the entire system.

The three main "killer features" are:

1. Reusable Attribute Library
2. Customizable Position Templates
3. Automatically Generated CVs

The application should prioritize correctness, simplicity, maintainability, and understandable code over unnecessary complexity.

---

# 2. Mandatory Technology

The project uses the PHP stack.

Required:

- PHP
- Symfony
- MySQL or PostgreSQL

Current technology decisions:

- PHP 8.4
- Symfony 7.4 LTS
- Doctrine ORM
- Doctrine Migrations
- PostgreSQL
- Twig
- JavaScript where necessary
- CSS framework

Current CSS/UI approach:

- Bootstrap is acceptable and preferred unless there is a strong reason to use another framework.

The application must use an ORM.

Do not replace Symfony with another backend framework.

Do not replace PHP with another backend language.

---

# 3. Architecture Philosophy

Keep the architecture simple.

This is a university/course project, not a production enterprise platform.

Prefer:

Controller
→ Repository / Doctrine
→ Entity
→ PostgreSQL

and:

Controller
→ Twig
→ HTML/CSS/JavaScript

Do NOT introduce unnecessary:

- Microservices
- Complex API layers
- CQRS
- Event sourcing
- Excessive abstraction
- Unnecessary DTO layers
- Complex frontend frameworks
- Custom implementations when a suitable library/component already exists

The project should be easy for the developer to understand and defend.

Every significant piece of code should have a clear reason for existing.

Use existing libraries/components whenever appropriate.

---

# 4. Important Course Instruction

The instructor explicitly emphasizes understanding the code.

The project will be evaluated not only by functionality but also by the developer's ability to:

- Explain the code.
- Explain architectural decisions.
- Modify the code during the defense.
- Answer technical questions.
- Explain why each important part exists.

Therefore:

> Prefer a smaller, fully understood implementation over a large implementation that the developer cannot explain.

Do not generate unnecessarily complicated code.

Do not blindly copy code.

When implementing a feature, understand the data flow and the reason for each important class, method, relationship, and query.

---

# 5. Deployment Requirement

The project must always have a deployable version.

The instructor explicitly requires:

> Start by deploying a static "Hello, world" page and always keep a deployable version ready.

Current status:

- The Symfony application has already been deployed.
- PostgreSQL has been configured/deployed.
- The project is connected to GitHub/Render.

Do not make changes that knowingly leave the project in an undeployable state for a long period.

After major changes:

1. Run the relevant checks.
2. Verify migrations.
3. Verify the application locally.
4. Keep the deployed version working whenever practical.

---

# 6. User Roles

There are three roles.

## Candidate

Candidates can:

- Manage their own personal profile.
- Select attributes from the Attribute Library.
- Fill attribute values.
- Manage projects.
- View positions available to them.
- Create CVs for positions they can access.
- Edit their own CVs.
- Delete their own CVs.
- Publish their CVs.
- Participate in discussions.

Candidates can access only:

- Their own profile.
- Their own CVs.
- Positions available to them.

---

## Recruiter

All Recruiters share the same system-wide positions and Attribute Library.

There is NO position ownership.

Any Recruiter can:

- Create positions.
- Duplicate positions.
- Edit positions.
- Delete positions.
- Configure position access rules.
- Manage position templates.
- Manage the Attribute Library.
- View candidate CVs in read-only mode.
- Search CVs.
- Access CVs through positions.
- Access CVs through appropriate public/read-only views.
- Participate in discussions.
- Like CVs.

Recruiters must NOT edit candidate CV data.

---

## Administrator

Administrators have unrestricted access.

An Administrator can:

- View all pages.
- Edit any candidate profile.
- Edit any candidate CV.
- Edit any position.
- Perform all Recruiter actions.
- Perform all Candidate actions.
- Manage users.
- Block users.
- Unblock users.
- Delete users.
- Assign roles.
- Remove roles.

An Administrator may remove their own Administrator role.

---

# 7. Authentication

Unauthenticated users may:

- Register.
- Sign in.
- Browse available positions in read-only mode.
- View public statistics.

Unauthenticated users may NOT:

- Create positions.
- Edit positions.
- Browse candidate CVs.
- Create CVs.
- Post discussion comments.
- Like CVs.
- Access personal pages.

Social authentication is required.

At minimum, support two social login providers.

Good candidates:

- Google
- Facebook

The exact providers can be selected based on implementation practicality.

---

# 8. Domain Model

The application revolves around these main concepts:

- User
- Attribute
- Attribute Category
- Candidate Attribute Value
- Project
- Position
- Position Attribute
- Position Access Rule
- CV
- Discussion Comment
- CV Like

The most important relationship is:

User
→ Candidate Profile Data
→ Projects
→ CVs

Position
→ Position Attributes
→ Attribute Library

CV
→ Candidate
→ Position

The CV should be generated from the relationship between:

Candidate profile data

- Position template
- Candidate projects

    ***

# 9. Attribute Library

The Attribute Library is one of the three main killer features.

Recruiters share one global Attribute Library.

Recruiters can:

- Create attributes.
- Edit attributes.
- Delete attributes.

Each attribute contains:

- Category
- Globally unique name
- Description
- Data type

Examples of categories:

- Certification
- Domain Knowledge
- Personal Information
- Soft Skills

Supported attribute types:

1. String
    - Single-line plain text.

2. Text
    - Markdown-formatted text.

3. Image
    - External cloud storage.
    - Images must NOT be uploaded to the web server or database.

4. Numeric

5. Date

6. Period
    - Date range.

7. Boolean
    - Checkbox.

8. One of many
    - Dropdown/select with predefined options.

Attribute selection should support:

- Prefix lookup.
- Recently used attributes.
- Category filtering.

Avoid loading the entire attribute library unnecessarily.

---

# 10. Attribute Values

Candidates can select attributes from the library and provide values.

Example:

Attribute:

IELTS Score
type = Numeric

Candidate:

John Smith

Candidate Attribute Value:

IELTS Score = 7.5

The attribute definition belongs to the shared Attribute Library.

The candidate's value belongs to the candidate/profile.

The same attribute may be reused across:

- Multiple candidate profiles.
- Multiple positions.
- Multiple CVs.

Do NOT duplicate attribute definitions.

---

# 11. Built-in Profile Attributes

Every authenticated user has a personal profile.

The "Me" section contains mandatory built-in attributes.

Examples:

- First Name
- Last Name
- Location
- Personal Photo

These attributes:

- Always exist.
- Cannot be removed by Recruiters.
- Must use the same general attribute engine/concept where practical.
- Can be used in position templates.

Administrators may edit candidate profile information.

---

# 12. Candidate Profile

The profile has four main sections.

## Me

Mandatory built-in attributes.

Examples:

- First Name
- Last Name
- Location
- Personal Photo

---

## Info

Candidate-selected attributes from the Attribute Library.

Candidates can:

- Add attributes.
- Remove attributes.
- Fill attribute values.
- Edit attribute values.

---

## Projects

Candidates maintain reusable projects.

Each project contains:

- Name
- Period / date range
- Description
- Technology Tags

Description supports Markdown.

Technology tags should support autocomplete from previously entered tags.

Use an existing UI component/library where practical instead of building a complicated custom tag-input component.

Projects can be:

- Added.
- Edited.
- Removed.

---

## CVs

The profile displays the candidate's CVs.

A candidate may have:

> At most one CV per position.

CV entries link to their CV page.

Candidates can:

- Create CVs.
- Edit CVs.
- Delete CVs.
- Publish CVs when valid.

---

# 13. Projects and Tags

Projects are reusable candidate profile data.

Each project has:

- Name
- Start date
- End date
- Markdown description
- Technology tags

Tags should support:

- Reuse.
- Autocomplete.
- Searching/filtering where useful.

Do not over-engineer the tag system.

---

# 14. Positions

Positions are customizable CV templates.

All Recruiters share one global list of positions.

There is no ownership of positions.

Recruiters can:

- Create.
- Duplicate.
- Edit.
- Delete.

A position contains:

- Title
- Short Description
- Access Rules
- Selected Attributes
- Project Tags
- Maximum number of projects

Optional useful fields:

- Company
- Level

Possible levels:

- Junior
- Middle
- Senior
- C-level

These optional fields can help filtering/sorting but are not more important than the core requirements.

---

# 15. Position Access Rules

A position can be:

1. Public to all authenticated users.

OR

2. Restricted using attribute-based filters.

Examples:

IELTS Score > 7.0

Remote Work = true

Presentation Skills = Advanced

Available operators depend on the attribute type.

For example:

Numeric:

- =
- !=
- >
- > =
- <
- <=

Boolean:

- true
- false

Dropdown:

- equals
- possibly not equals

String:

- equals
- contains/prefix where appropriate

Date:

- before
- after
- equals

The implementation should use the simplest understandable approach.

---

# 16. CV Access and Position Access

Candidates may create a CV only when they are authorized to access the position.

If a candidate loses access to a position:

- Existing CVs are NOT deleted.
- Existing CVs become hidden from the candidate/recruiter UI according to the access rules.

Do not physically delete CVs just because access is lost.

---

# 17. CV Generation

CVs are generated automatically.

A CV combines:

1. Candidate built-in profile data.
2. Candidate-selected Attribute Library values.
3. Position-required attributes.
4. Relevant candidate projects.

The position determines which attributes are relevant.

Projects are filtered according to the position's project tags and maximum project count.

The generated CV should be:

- Professionally formatted.
- Clearly structured.
- Easy to read.
- Responsive.
- Limited to relevant information.

---

# 18. CV Attribute Editing

This is an important rule.

There is one common master value for an attribute in the candidate profile.

When a candidate edits an attribute inside their CV:

> The candidate's original profile attribute value is modified.

The CV should NOT create an unrelated duplicate master value.

If an attribute is required by the position but the candidate has no value:

- It should appear empty.
- Empty values should be highlighted in red.

When the candidate enters the value through the CV:

- The profile value should be created/updated.
- The CV then reflects that profile value.

---

# 19. CV Status / Publishing

CVs should have a state.

At minimum:

- Draft
- Published

Publishing should only be possible when all required attributes are filled.

Draft CVs are not visible to Recruiters.

Published CVs become available to Recruiters according to position access rules.

If required information is missing:

- Highlight it.
- Do not allow publishing.

---

# 20. Recruiter CV View

Recruiters see CVs in rendered read-only mode.

Recruiters can:

- View.
- Search.
- Like.
- Participate in discussions where applicable.

Recruiters cannot directly modify candidate CV data.

Empty values remain highlighted in red.

Administrators can modify CVs.

---

# 21. CV Likes

Only Recruiters may like CVs.

Rules:

- One Recruiter can give at most one like to a specific CV.
- Recruiters can remove their like.
- Total likes must be displayed.
- Like counts appear in CV lists and search results.

The database should enforce uniqueness where appropriate.

---

# 22. Discussions

Each Position contains a Discussion tab.

Discussion posts contain:

- Author
- Timestamp
- Markdown-formatted text

Posts are displayed chronologically.

New posts are always appended.

Posts cannot be inserted between existing posts.

When viewed by Recruiters, the author name should link to the appropriate public profile view.

Updates should appear for active viewers within approximately:

2–5 seconds.

Simplest acceptable implementation:

- Polling.

WebSockets are optional and should NOT be introduced unless there is a clear reason.

---

# 23. Optimistic Locking

The system MUST use optimistic locking.

Relevant records include:

- Candidate profile data.
- Attributes where appropriate.
- Positions.
- Profile auto-save.

Each save operation should:

1. Send the current version number.
2. Check whether the stored version still matches.
3. Update only if versions match.
4. Increase the version.
5. Return the new version.
6. Fail if the version has changed.

Example:

Current database version:

5

Client sends:

version = 5

Update succeeds.

Database becomes:

version = 6

Another client sends:

version = 5

Update fails because the database is already version 6.

The client should handle this conflict gracefully.

---

# 24. Profile Auto-Save

Personal profile pages support automatic saving.

Requirements:

- Track changes locally.
- Do NOT save on every keystroke.
- Save approximately every 5–10 seconds.
- Use optimistic locking.
- Handle version conflicts gracefully.

A reasonable implementation is:

- Track dirty state.
- Debounce/throttle changes.
- Save periodically.
- Send version number.
- Receive updated version.

Do not create a complex real-time synchronization system unless required.

---

# 25. Full-Text Search

Every page provides access to full-text search through the top header.

The project must use a real full-text search mechanism.

Do NOT perform inefficient full database scans.

Possible implementation:

- PostgreSQL full-text search.

The exact implementation should prioritize simplicity and explainability.

Search should cover relevant content such as:

- Positions.
- CVs.
- Candidate/project information where appropriate.

Search results must respect permissions.

A user must never see search results for data they are not authorized to access.

---

# 26. Main Page

The main page contains:

## Latest Positions

A table showing recently created or updated positions.

## Most Popular Positions

Top 5 positions ranked by submitted CV count.

## Tag Cloud

Technology tags.

For Candidates:

- Tags can link to relevant positions.

For Recruiters:

- Tags can link to relevant CVs.

## Statistics

Examples:

- CVs created in the last 24 hours.
- Total positions.
- Total Candidates.
- Total Recruiters.
- Total submitted CVs.

---

# 27. Internationalization

The UI must support two languages.

Required:

- English
- One additional language

Preferred additional language:

- Uzbek

Only UI text is translated.

User-generated content is NOT translated.

Examples of user-generated content:

- Project names.
- Project descriptions.
- Candidate-written text.
- Position descriptions.
- Discussion posts.

The user's selected language must be saved.

Use Symfony's translation/i18n mechanisms where possible.

Do not build a custom translation engine.

---

# 28. Themes

The application supports:

- Light theme.
- Dark theme.

The user's choice must be saved.

The theme should apply consistently throughout the application.

Use CSS framework capabilities and simple custom CSS where needed.

Avoid creating an unnecessarily complicated theme system.

---

# 29. Responsive Design

The application must support:

- Desktop.
- Tablet.
- Mobile phones.

The UI should remain usable at different screen sizes.

Use the CSS framework's responsive utilities/components wherever possible.

---

# 30. CRITICAL UI GRADING RULE — TABLES

Positions and CVs MUST be displayed as tables.

Do NOT use:

- Cards as the primary representation.
- Tiles.
- Galleries.

This will receive a significant grading penalty.

Correct:

- Position table.
- CV table.

Incorrect:

- Grid of position cards.
- Grid of CV cards.

---

# 31. CRITICAL UI GRADING RULE — NO BUTTON PER ROW

Do NOT put individual View/Edit/Delete buttons in every table row.

This is explicitly forbidden and can result in a -20% grade penalty.

Bad:

Name | Position | Edit | Delete

Bad:

John | BA | [View] [Edit] [Delete]

Preferred approach:

- Select row(s) using checkboxes.
- Use a toolbar for actions.
- Use a contextual/appearing action menu.
- Use a single toolbar action for selected records.
- Use row selection + global actions.

The table should remain clean.

Avoid N buttons for N records.

---

# 32. No Gallery/Tiles for Positions or CVs

Positions:

MUST use table representation.

CVs:

MUST use table representation.

Do not replace these tables with:

- Cards
- Tiles
- Galleries

Other parts of the application may use cards when appropriate, but not as the primary representation of positions or CVs.

---

# 33. Navigation

Navigation must be:

- Consistent.
- Predictable.
- Responsive.
- Accessible from relevant pages.

The top header should provide:

- Main navigation.
- Authentication state.
- Global search.
- Language selection.
- Theme selection.

Navigation should change according to user permissions.

Do not expose links to unauthorized pages.

---

# 34. Images

Images must NOT be uploaded to:

- The web server.
- The PostgreSQL database.

For personal photos and Attribute Library image values:

Use an external cloud storage/image hosting service.

Use a ready-made image upload component/library when possible.

Do not write a custom image storage system unless absolutely necessary.

---

# 35. Markdown

Markdown is required for:

- Project descriptions.
- Discussion posts.
- Text-type attributes.

Use an existing Markdown parsing/rendering library.

Do NOT implement a Markdown parser manually.

Sanitize rendered content appropriately.

---

# 36. Database Rules

Use Doctrine ORM.

Use proper relationships.

Avoid unnecessary raw SQL.

Do not perform:

SELECT \*

over entire large datasets.

Do not perform full database scans when a proper query/filter can be used.

Do not execute database queries inside loops.

Avoid N+1 query problems.

Use Doctrine relations and appropriate queries.

Prefer repository methods for non-trivial queries.

---

# 37. Query Rules

IMPORTANT:

Do NOT:

- Execute queries inside loops.
- Load huge datasets when only a subset is needed.
- Use SELECT \* unnecessarily.
- Perform database scans to filter data in PHP when the database can filter it.
- Repeatedly query the same entity inside loops.

Prefer:

- QueryBuilder.
- Repository methods.
- Joins.
- Pagination.
- Aggregation queries.

---

# 38. Repositories

Repositories should contain database-specific retrieval/query logic.

Controllers should NOT contain large database query logic.

For example:

PositionRepository:

- findLatest()
- findPopular()
- searchPositions()
- findAccessiblePositions()

CVRepository:

- findCandidateCVs()
- findPublishedCVs()
- searchCVs()
- countPublishedCVs()

Exact methods should be added only when actually needed.

Do not create repository methods just for the sake of abstraction.

---

# 39. Entities

Current project has already started building Doctrine entities and repositories.

Existing/core entities include concepts such as:

- User
- Category
- Task
- Comment
- Project
- Position
- CV
- Attribute

The exact final model should be reviewed before adding unnecessary entities.

Important:

Do not blindly change an existing entity.

First inspect:

- Existing fields.
- Existing relationships.
- Existing migrations.
- Existing repositories.
- Existing controllers/forms.

Preserve working functionality.

---

# 40. Current Project Status

Current state:

- Symfony project created.
- PHP stack selected.
- PostgreSQL selected.
- Doctrine ORM configured.
- Doctrine migrations configured.
- Project deployed.
- Initial database work completed.
- Entities and repositories have been started.
- Database migrations have already been generated/used during development.

Current development focus:

> Complete and validate the domain model/entities/repositories before building the majority of the application UI and business logic.

Do not assume the existing entity model is perfect.

Review it against this document before implementing dependent features.

---

# 41. Development Phases

Use the following implementation order unless there is a strong technical reason to change it.

## Phase 1 — Infrastructure

- Symfony configuration.
- PostgreSQL.
- Doctrine.
- Migrations.
- Deployment.
- Basic Hello World.

STATUS: Mostly complete.

---

## Phase 2 — Domain Model

Build and validate:

- User.
- Roles.
- Attribute.
- Attribute Category.
- Candidate Attribute Value.
- Project.
- Tags.
- Position.
- Position Attribute.
- Access Rules.
- CV.
- Comments.
- Likes.

Validate relationships before proceeding.

STATUS: In progress.

---

## Phase 3 — Authentication and Authorization

Implement:

- Login.
- Registration.
- Social authentication.
- Candidate role.
- Recruiter role.
- Administrator role.
- Access control.

---

## Phase 4 — Attribute Library

Implement:

- Attribute list.
- Create.
- Edit.
- Delete.
- Categories.
- Attribute types.
- Attribute values.
- Prefix lookup.
- Recently used.
- Category filtering.

---

## Phase 5 — Candidate Profile

Implement:

- Me.
- Info.
- Projects.
- Tags.
- CV list.
- Profile editing.
- Administrator profile editing.

---

## Phase 6 — Positions

Implement:

- Position table.
- Create.
- Duplicate.
- Edit.
- Delete.
- Attribute selection.
- Project tag configuration.
- Maximum projects.
- Access rules.

---

## Phase 7 — CV Generation

Implement:

- Create CV from position.
- Pull profile attributes.
- Render required attributes.
- Render filtered projects.
- Empty-value highlighting.
- In-place editing.
- Profile value synchronization.
- Draft/published state.

---

## Phase 8 — Recruitment Features

Implement:

- Recruiter CV table.
- CV search.
- CV likes.
- Position CV lists.
- Permission-aware access.
- Public/read-only position views.

---

## Phase 9 — Discussions

Implement:

- Position discussion.
- Markdown.
- Chronological posts.
- Polling updates.

---

## Phase 10 — Autosave and Optimistic Locking

Implement:

- Version fields.
- Version checking.
- Conflict handling.
- Profile dirty-state tracking.
- Periodic auto-save.

---

## Phase 11 — Search

Implement:

- Global header search.
- PostgreSQL full-text search.
- Permission-aware results.
- Position search.
- CV search.
- Relevant candidate/project search.

---

## Phase 12 — UI Polish

Implement:

- Bootstrap styling.
- Responsive design.
- Light theme.
- Dark theme.
- English.
- Uzbek.
- Consistent navigation.
- Table toolbars.
- Clean empty/loading/error states.

---

## Phase 13 — Testing and Defense

Verify:

- Authentication.
- Authorization.
- Candidate restrictions.
- Recruiter restrictions.
- Admin privileges.
- Attribute types.
- Position access rules.
- CV generation.
- CV publishing.
- Likes.
- Discussions.
- Search.
- Optimistic locking.
- Autosave.
- Mobile layout.
- Dark/light themes.
- Translations.

Also prepare to explain:

- Every entity.
- Important relationships.
- Important controllers.
- Repository queries.
- Authentication.
- Authorization.
- Optimistic locking.
- CV generation.
- Search.
- Why libraries were selected.

---

# 42. Optional Features

Only implement optional features AFTER all core requirements work correctly.

Optional:

- PDF CV generation.
- QR codes linking back to the application.
- Email confirmation authentication.
- Badges/achievements.
- SVG achievement panel.
- Advanced attribute validation.
- Numeric ranges.
- Regex validators.
- Text length limits.
- CSV/Excel export.

Do NOT sacrifice core functionality for optional features.

---

# 43. Priority Order

When deciding what to implement next:

### Priority 1 — Mandatory grading requirements

Examples:

- Tables for Positions/CVs.
- Authentication.
- Roles.
- Attribute Library.
- Position templates.
- CV generation.
- Access rules.
- Search.
- Optimistic locking.
- Autosave.
- Discussions.
- Likes.
- Themes.
- Languages.
- Responsive UI.

### Priority 2 — Quality

- Good UX.
- Validation.
- Error handling.
- Performance.
- Clean architecture.
- Good database queries.

### Priority 3 — Optional features

Only after Priority 1 and 2 are stable.

---

# 44. Cursor / AI Rules

When working on this project, ALWAYS read this file before planning major changes.

This document is the project's high-level source of truth.

However:

> The actual source code is the source of truth for what is currently implemented.

Therefore, before modifying code:

1. Inspect the existing implementation.
2. Compare it with this document.
3. Identify what is already implemented.
4. Identify missing functionality.
5. Avoid recreating existing functionality.
6. Avoid changing unrelated code.
7. Keep changes focused.

When asked to create a plan:

- First inspect the current project.
- Identify relevant existing entities/controllers/repositories/templates.
- Explain dependencies between changes.
- Prefer incremental implementation.

Do not assume that a requirement is already implemented simply because it appears in this document.

---

# 45. Cursor Coding Rules

When implementing functionality:

- Prefer Symfony conventions.
- Prefer Doctrine ORM.
- Prefer existing Symfony components.
- Prefer existing Bootstrap components.
- Prefer established libraries for Markdown, tags, image upload, etc.
- Avoid unnecessary custom JavaScript.
- Avoid unnecessary custom CSS.
- Avoid unnecessary abstractions.
- Keep controllers understandable.
- Keep business logic understandable.
- Keep repository queries focused.
- Use validation.
- Use authorization.
- Avoid database queries inside loops.
- Avoid SELECT \*.
- Avoid N+1 queries.

Before adding a new library, ask:

> Is this actually necessary?

Before writing custom functionality, ask:

> Is there already a suitable Symfony/Bootstrap/library component?

---

# 46. UI Rules for Cursor

When creating tables for Positions or CVs:

DO:

- Use tables.
- Use checkboxes for selection.
- Use a toolbar.
- Use contextual actions.
- Use sorting.
- Use pagination where needed.
- Use search/filter controls.

DO NOT:

- Add Edit/Delete buttons to every row.
- Create a card grid.
- Create a tile layout.
- Create a gallery.

Remember:

> The table-row button rule is a grading requirement, not merely a design preference.

---

# 47. Security Rules

Always enforce permissions server-side.

Never rely only on hiding buttons in Twig.

For every protected operation:

- Check authentication.
- Check role.
- Check ownership/access rules.
- Validate input.

Candidates must not access other candidates' private profile data.

Recruiters must not edit candidate CVs.

Draft CVs must not be exposed to Recruiters.

Search must respect permissions.

Administrators have unrestricted access.

---

# 48. Performance Rules

Avoid:

- Queries inside loops.
- N+1 relationship loading.
- Full database scans.
- Loading unnecessary records.
- SELECT \* when only a few columns are required.

Use:

- Pagination.
- QueryBuilder.
- Joins.
- Aggregations.
- Full-text search.
- Appropriate indexes.

Performance optimizations should remain understandable.

---

# 49. Validation

Validate data at the appropriate layer.

Examples:

- Required position title.
- Unique attribute name.
- Valid numeric values.
- Valid dates.
- Valid periods.
- Valid dropdown values.
- Required CV attributes before publishing.
- Valid access rules.

Do not rely solely on frontend validation.

Important validation must also happen server-side.

---

# 50. Error Handling

The application should gracefully handle:

- Unauthorized access.
- Missing records.
- Invalid forms.
- Optimistic-lock conflicts.
- Invalid attribute values.
- Position access changes.
- Database errors where appropriate.

Do not expose sensitive internal errors to normal users.

---

# 51. Definition of Done

A feature is NOT considered complete merely because the page exists.

A feature is complete when:

- The database model supports it.
- Server-side validation exists.
- Authorization exists.
- The UI works.
- Error states are handled.
- Relevant queries are efficient.
- It works on mobile where applicable.
- It does not violate the table/button grading requirements.
- The implementation can be explained by the developer.

---

# 52. Important Deadline

Project submission date:

23 September 2026

The goal is NOT to implement every optional feature.

The goal is:

> Build a complete, stable, understandable implementation of the core requirements.

---

# 53. Final Guiding Principles

1. Keep it simple.
2. Understand everything that is written.
3. Use libraries instead of reinventing components.
4. Use Doctrine ORM correctly.
5. Avoid database queries inside loops.
6. Avoid unnecessary raw SQL.
7. Do not use SELECT \* unnecessarily.
8. Respect permissions.
9. Positions and CVs MUST be tables.
10. Do NOT put action buttons in every table row.
11. Keep the project deployable.
12. Implement core requirements before optional features.
13. Prefer understandable code over clever code.
14. Inspect existing code before modifying it.
15. Never blindly rewrite working functionality.
16. Every major architectural decision should have a reason.
17. The project must be defensible: the developer should be able to explain how and why it works.

---

# 54. Current Immediate Goal

Before implementing large amounts of application logic:

1. Inspect all current entities.
2. Inspect all repositories.
3. Inspect current migrations.
4. Compare the database model against this document.
5. Identify missing relationships.
6. Fix the domain model if necessary.
7. Generate/update migrations.
8. Verify the database schema.
9. Only then proceed to authentication and application logic.

Do not rush into building all pages before the domain model is stable.
