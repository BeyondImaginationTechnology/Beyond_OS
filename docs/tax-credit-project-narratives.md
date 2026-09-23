# Technical Tax-Credit Project Narratives

## Use and scope

These narratives are based on the technical work represented in the Beyond OS repository. They are a working draft for review with the company's tax adviser. Project dates, employee/contractor time, invoices, cloud costs, failed prototypes, test results, and business-purpose records should be added before filing. Routine configuration, content entry, visual design, and ordinary release administration should be separated from qualifying experimental work.

## 1. Beyond OS web platform and shared backend

**Technical objective:** Develop a reusable PHP/MySQL platform supporting identity, learning academies, certificates, media experiences, marketplaces, analytics, administration, scheduled jobs, and shared security controls across multiple products.

**Specific uncertainty:** It was uncertain whether one shared platform could support materially different product workflows, authentication states, privacy requirements, and deployment environments without creating unsafe coupling, data leakage, or unacceptable performance and maintenance overhead.

**Hypothesis:** A shared bootstrap/configuration layer, modular PHP endpoints, explicit SQL migrations, private runtime storage, and centralized security helpers could support the product family while preserving feature-level isolation.

**Experiment or attempted approach:** Implemented and iterated shared bootstrap, authentication, database, security, analytics, admin, cron, API, SQL migration, and deployment components; tested feature-specific flows including certificates, Beyond ID, content publishing, analytics, and private storage; patched behavior through successive platform releases.

**Result and next step:** The architecture supports the current web product family, but ongoing integration and security testing is required as new apps and social sign-in are added. Next steps are automated regression tests across shared services, migration testing from clean databases, load testing, and formal evidence capture for security and performance experiments.

## 2. Beyond ID identity, authorization, and social sign-in

**Technical objective:** Provide a secure identity layer that can be used by web, iOS, Android, and companion products, including session exchange, Keychain/secure-storage handling, account controls, and social sign-in.

**Specific uncertainty:** It was uncertain whether tokens, redirects, session cookies, PKCE flows, deep links, and platform-specific secure storage could be coordinated across browsers and native apps without exposing credentials or creating inconsistent account state.

**Hypothesis:** Strict origin allowlists, short-lived tokens, PKCE, Keychain/secure storage, explicit retry/expiry states, and server-side session validation would provide a safe cross-platform handoff.

**Experiment or attempted approach:** Built and revised web login redirects, native ASWebAuthenticationSession flows, PKCE exchange, Keychain persistence, deep links, account controls, social sign-in, and diagnostic logging; exercised expired-session, retry, malformed-response, and unauthenticated paths.

**Result and next step:** The identity flow is integrated into multiple native and web products, with further validation needed for provider edge cases and account recovery. Next steps are penetration testing, device/browser matrix testing, token-rotation tests, and audit logging for authorization decisions.

## 3. Beyond OS Home, desktop, Chromium, and mobile OS tracks

**Technical objective:** Determine whether a Beyond-branded operating-system experience could be assembled from upstream Linux/Buildroot and AOSP components, with a minimal desktop shell, browser/controller interaction, and a mobile command-center companion.

**Specific uncertainty:** It was uncertain whether the selected upstream components could boot reliably, identify as Beyond OS, render a usable graphical session, accept controller input, and preserve a secure boundary between the OS image, cloud GUI, and companion apps.

**Hypothesis:** A constrained Buildroot external tree, a minimal X.Org/Openbox/SDL2 shell, loopback-only VNC/noVNC, and conventional Android/iOS companion apps could validate the product architecture before hardware qualification.

**Experiment or attempted approach:** Created the Home Edition Buildroot tree, QEMU image path, release gates, cloud browser GUI scripts, controller-first Chromium wrapper, AOSP mobile track, and native companion prototypes; exercised build, boot, graphics, keyboard/mouse, controller, shutdown, and browser handoff paths where available.

**Result and next step:** The work establishes development prototypes, but the desktop image is not yet hardware-qualified or release-ready. Next steps are reproducible image builds, QEMU boot automation, hardware-driver testing, signed-update experiments, device security review, and end-to-end cloud GUI smoke tests.

## 4. Jaguar / Beyond-1 AI assistant

**Technical objective:** Create a teaching-focused assistant using a Llama 3.1 base model, a repeatable QLoRA training pipeline, a private GPU runtime, and native/web clients with Explain, Plan, Learn, and reviewer-demo experiences.

**Specific uncertainty:** It was uncertain whether a small reviewed dataset and LoRA adapter could reliably produce the desired teaching voice, prompt-improvement behavior, multilingual responses, acceptable latency, and cost-controlled inference on a scale-to-zero GPU service.

**Hypothesis:** A structured training dataset, QLoRA adapter, 4-bit inference, request-driven L4 deployment, fast-path handling for simple requests, and explicit client-side error states could meet the initial quality and operating-cost targets.

**Experiment or attempted approach:** Built dataset validation and Modal training scripts; trained and retrieved adapter artifacts; implemented local and Modal FastAPI runtimes, 4-bit/bfloat16 fallback, health checks, cold-start scheduling, PHP proxying, proof-of-work for guests, native SwiftUI client flows, timing display, retries, reviewer demo mode, and multilingual requests.

**Result and next step:** The end-to-end training, deployment, and client pipeline works as a preview, but quality and safety require a larger human-reviewed evaluation set. Next steps are benchmark design, response-quality scoring, adversarial/safety testing, latency and cold-start measurement, dataset expansion, and private release gating.

## 5. DailyBreath web, iOS, and Android

**Technical objective:** Deliver a cross-platform offline-first faith, breathing, journaling, recovery, and learning experience with multilingual scripture, search, daily content, widgets/deep links, optional encrypted sync, and scoped assistant conversations.

**Specific uncertainty:** It was uncertain whether large multilingual Bible/Tanakh/Quran libraries, search, daily synchronization, privacy-protected journaling, widgets/App Clip/deep links, and Android/iOS parity could operate correctly with intermittent or absent connectivity.

**Hypothesis:** Bundled content with background loading, local indexes/state, explicit stale/malformed/timeout handling, file-protected storage, optional encrypted iCloud sync, and platform-specific adapters could provide dependable offline behavior without forcing account creation.

**Experiment or attempted approach:** Implemented web PWA behavior, multilingual content selection, local scripture libraries/search, breathing state, journal protection, challenges/history, iOS widgets/App Clip/iCloud sync, Android navigation/deep links, release workflows, and guest-scoped chat; exercised online, offline, stale-response, timeout, and malformed-response paths.

**Result and next step:** Core offline and cross-platform slices are implemented, with release and device validation still required. Next steps are full content-integrity checks, sync-conflict testing, encryption/key-loss recovery tests, accessibility testing, battery/performance measurement, and store-build validation.

## 6. Beyond French language-learning platform

**Technical objective:** Build a daily French-learning system combining phrase content, pronunciation/audio, multilingual comparisons, lesson APIs, native clients, and generated short-form video.

**Specific uncertainty:** It was uncertain whether lesson content, generated narration, native audio playback, multilingual fallback, and web/native synchronization could remain consistent while supporting offline use and multiple regional language variants.

**Hypothesis:** A canonical lesson/content model, generated audio assets, local caching, server configuration endpoints, and reusable rendering components could keep daily lessons synchronized across web, iOS, Android, and promotional video outputs.

**Experiment or attempted approach:** Added phrase/lesson data, audio-generation scripts, native SwiftUI/Android clients, daily APIs, African language expansion compositions, browser Remotion rendering, and asset regeneration/synchronization tools; tested local playback, fallback audio, lesson preparation, browser rendering, and published configuration retrieval.

**Result and next step:** The content-to-app-to-video pipeline is functional, but pronunciation quality, regional correctness, and synchronization need systematic validation. Next steps are native-speaker review, audio regression testing, offline/online reconciliation tests, and automated lesson-schema validation.

## 7. French Quest iOS game

**Technical objective:** Develop an offline-first language-learning game with a travel map, sequential missions, translation/listening/culture challenges, phrasebook, audio, local progression, and cloud save.

**Specific uncertainty:** It was uncertain whether a game-state model could preserve progression, hearts, streaks, region unlocks, and cloud synchronization reliably across interrupted sessions and changing content while keeping audio playable offline.

**Hypothesis:** A local authoritative game state with explicit save/load synchronization, bundled audio, on-device fallback speech, and deterministic mission progression would provide resilient play across network conditions.

**Experiment or attempted approach:** Implemented SwiftUI map and mission flow, local XP/hearts/streak state, phrasebook and training room, bundled Azure audio, device fallback, soundtrack controls, Beyond ID Keychain auth, and cloud save/load with automatic sync.

**Result and next step:** The first game slice and account synchronization are implemented. Next steps are interruption and conflict testing, content/audio QA, progression-abuse testing, device performance checks, and learner-outcome measurement.

## 8. Beyond Tattoo digital library, stencil, and studio systems

**Technical objective:** Create a daily tattoo-stencil system that manages approved assets, watermarked previews, print-ready downloads, release schedules, healing milestones, rewards, studio discovery, and native clients.

**Specific uncertainty:** It was uncertain whether asset approval, scheduled publication, preview/download variants, location-aware studio data, and native/web clients could remain consistent while preventing premature or incorrect asset release.

**Hypothesis:** An approval-and-release state machine, filesystem existence checks, separate preview/print assets, generated manifests, and shared APIs could make the catalog deterministic and safe to publish.

**Experiment or attempted approach:** Implemented asset import/migration tools, approval APIs, scheduled drop logic, stencil library and generator, watermarked/print-ready checks, Apple client, studio directory, supply-store flows, healing/reward state, and reusable Remotion stencil videos.

**Result and next step:** The catalog and publication pipeline are operational, while asset completeness, download security, and device behavior require continued testing. Next steps are end-to-end approval-to-publication tests, signed/expiring download URLs, asset checksum validation, location-data testing, and creator/studio workflow trials.

## 9. Beyond TV and media playback

**Technical objective:** Provide a shared channel and media experience across web, Android, iOS, iPad, and Apple TV, including playback review, channel operations, catalogs, and audio/video integrations.

**Specific uncertainty:** It was uncertain whether a canonical channel/catalog model and playback layer could behave consistently across AVPlayer, Android, web browsers, different screen sizes, intermittent networks, and changing source availability.

**Hypothesis:** A shared content model with platform-specific playback adapters, fallback artwork/state, and explicit availability handling would preserve a consistent viewer experience across clients.

**Experiment or attempted approach:** Implemented web product areas, Apple AVPlayer clients, Android shell, channel/catalog tools, app-store assets, release workflows, and media proxy/conversion components; exercised channel listings, playback states, responsive layouts, and deployment paths.

**Result and next step:** Cross-platform client foundations exist, but source availability, playback resilience, and device coverage need measured validation. Next steps are codec/device matrix tests, buffering and recovery experiments, catalog synchronization checks, and playback telemetry.

## 10. Beyond Baby Names

**Technical objective:** Build private, offline-first name discovery with search, filters, stories, swipe decisions, favorites, couple matching, twin ideas, family-name preview, and PWA/native clients.

**Specific uncertainty:** It was uncertain whether private user choices could support couple matching and synchronization without sending sensitive shortlist or family-name data to the server or allowing one partner to read the other's private decisions.

**Hypothesis:** Browser-local storage for personal data, hashed invite/member credentials, a two-member SQLite couple space, and match disclosure only after independent agreement would satisfy the privacy and collaboration requirements.

**Experiment or attempted approach:** Implemented local recommendation/search flows, PWA behavior, Android/iOS clients, couple API, hashed invite/token storage, two-member limits, per-member decision access, mutual-match disclosure, expiry, and cloud synchronization paths.

**Result and next step:** The privacy model and product slice are implemented. Next steps are rate-limit testing, threat modeling, backup/expiry validation, synchronization conflict tests, and usability testing with representative couple workflows.

## 11. Beyond Health

**Technical objective:** Develop a calendar-first native health and family-health logging MVP with a clear data model and private user experience.

**Specific uncertainty:** It was uncertain whether the event model could represent recurring, family-linked, and calendar-oriented health records without ambiguous dates, unsafe exposure of sensitive information, or an unusable navigation model.

**Hypothesis:** A calendar-first SwiftUI model with explicit record ownership, local-first state, and constrained MVP workflows would provide a usable foundation while keeping future clinical/data integrations optional.

**Experiment or attempted approach:** Built the native calendar-first MVP, record flows, family-oriented views, and supporting app structure; iterated on navigation, state, and privacy boundaries.

**Result and next step:** The MVP establishes the core interaction model, but validation of data semantics, privacy, accessibility, and real-world logging remains. Next steps are schema review, usability studies, secure-storage testing, and explicit non-medical positioning review.

## 12. Beyond Space, Math, Games, Ancient, Preschool, Radio, Skate, Casino, Jobs, and related verticals

**Technical objective:** Prototype reusable product patterns for educational, entertainment, science, career, and content verticals within the Beyond ecosystem, including daily facts, math experiences, games, radio/media, and catalog-driven pages.

**Specific uncertainty:** It was uncertain which shared navigation, content, state, and rendering patterns could be reused across verticals without degrading the specialized interaction, offline behavior, or content quality required by each experience.

**Hypothesis:** Shared design tokens, API/bootstrap helpers, content generators, native shells, and modular product folders could reduce duplicated engineering while allowing each vertical to retain its own domain model.

**Experiment or attempted approach:** Created separate web and native product tracks, content libraries, APIs, game prototypes, daily fact generators, design tokens, release assets, and shared platform integrations; compared reuse across the verticals and iterated where domain requirements differed.

**Result and next step:** The repository demonstrates reusable product foundations and multiple prototypes, but each vertical needs its own evidence before being treated as a separate qualifying project. Next steps are to select active verticals, define measurable technical acceptance criteria, record experiments and failures by project, and avoid claiming routine content production as R&D.

## 13. Beyond Market, Sell, Wallet, and payments

**Technical objective:** Develop marketplace, seller, fulfillment, checkout, and wallet experiences that can connect listings, identity, payment state, and user-owned transactions across web and native clients.

**Specific uncertainty:** It was uncertain whether listings, seller tools, checkout state, fulfillment events, and wallet balances could remain consistent across asynchronous services and client interruptions without duplicate, lost, or unauthorized transactions.

**Hypothesis:** Explicit transaction states, idempotent server operations, authenticated ownership checks, and client reconciliation would provide a reliable foundation for marketplace and wallet workflows.

**Experiment or attempted approach:** Implemented marketplace/seller areas, wallet-related native shell work, connected listings and checkout/fulfillment flows, account integration, and supporting database/API structures; iterated on state and release integration.

**Result and next step:** The portfolio contains the initial connected product flows, but transaction correctness and payment-provider integration require focused validation. Next steps are idempotency and replay tests, reconciliation jobs, authorization review, sandbox payment testing, and ledger/invariant checks.

## 14. Beyond Studio, Remotion, and generated media tooling

**Technical objective:** Render trusted Remotion/React and browser-based compositions into repeatable MP4 assets from a PHP-hosted administration workflow without granting arbitrary uploaded code access to the public server.

**Specific uncertainty:** It was uncertain whether browser rendering and a local/cloud bridge could provide deterministic frame control, acceptable H.264 output, artifact isolation, and safe AI-triggered rendering while avoiding Node/Chromium/FFmpeg requirements on the PHP host.

**Hypothesis:** A separate token-protected bridge, trusted-artifact approval, browser renderer, virtual animation clock, composition allowlisting, and scoped AI API could satisfy rendering and security requirements.

**Experiment or attempted approach:** Built the Remotion bridge, trusted ZIP/HTML artifact handling, browser renderer, screen-record fallback, composition projects for French and Tattoo, local/cloud deployment guides, token scopes, and AI render endpoints; ran smoke tests and generated sample outputs.

**Result and next step:** The rendering architecture and reusable compositions work in development, but deterministic output, resource limits, and artifact security need production evidence. Next steps are reproducibility tests, malicious-artifact testing, job cancellation/cleanup tests, render queue limits, and output quality/size benchmarks.

## 15. Academy, coding school, certificates, and learning infrastructure

**Technical objective:** Provide reusable learning pathways, lessons, assessments, practice, certificates, verification, and administrative controls across the Beyond ecosystem.

**Specific uncertainty:** It was uncertain whether learner progress, assessment results, certificate issuance, and verification could remain tamper-resistant and consistent across sessions, pathways, and administrative corrections.

**Hypothesis:** Server-side progress records, controlled assessment transitions, generated certificate identifiers, verification endpoints, and shared learning UI components could provide reliable learning-state management.

**Experiment or attempted approach:** Implemented academy dashboards, pathways, lessons, assessments, practice flows, certificate generation/verification, admin controls, SQL schemas, and shared learning styles/scripts; iterated on state transitions and public verification behavior.

**Result and next step:** The learning platform is integrated into the web ecosystem, with further testing required for concurrent updates, certificate integrity, accessibility, and recovery from partial saves. Next steps are state-machine tests, authorization tests, certificate tamper tests, migration tests, and learner usability studies.

## Evidence and recordkeeping checklist

For each project, retain a project code, technical lead, start/end dates, qualifying personnel, time records, contractor invoices, cloud/GPU/build costs, design notes, issue history, source-control commits, test plans, failed approaches, measured results, and the decision that followed each experiment. Keep customer-facing feature work, routine maintenance, content production, app-store administration, and ordinary bug fixes separately identified unless they directly support an eligible technological experiment.
