(function () {
  var dictionaries = {
    en: {
      language: 'Choose language', apps: 'Apps', health: 'Health', education: 'Education', wallet: 'Wallet', entertainment: 'Entertainment',
      heroHealth: 'Health.', heroEducation: 'Education.', heroWallet: 'Wallet.', heroEntertainment: 'Entertainment.',
      tagline: 'Live. Learn. Earn. Explore.', intro: 'Everything you need to grow, create and discover—connected in one ecosystem.', explore: 'Explore the Ecosystem ▶',
      healthHeadline: 'Live your best life.', healthCopy: 'Mind, body and soul. Everything you need to feel better every day.',
      educationHeadline: 'Knowledge without limits.', educationCopy: 'Learn anything. Anywhere. Unlock your potential across every subject.',
      walletHeadline: 'Spend, earn and cash out.', walletCopy: 'Your bit$, purchases and verified creator earnings in one customer-friendly wallet.',
      entertainmentHeadline: 'Explore what moves you.', entertainmentCopy: 'Watch, listen, create and discover something new across the Beyond universe.',
      dailyFrench: 'French phrase of the day', dailyVerse: 'Bible verse of the day', practice: 'Practice now →', read: 'Read & listen →'
    },
    fr: {
      language: 'Choisir la langue', apps: 'Apps', health: 'Santé', education: 'Éducation', wallet: 'Portefeuille', entertainment: 'Divertissement',
      heroHealth: 'Santé.', heroEducation: 'Éducation.', heroWallet: 'Portefeuille.', heroEntertainment: 'Divertissement.',
      tagline: 'Vivre. Apprendre. Gagner. Explorer.', intro: 'Tout ce qu’il vous faut pour grandir, créer et découvrir—réuni dans un seul écosystème.', explore: 'Explorer l’écosystème ▶',
      healthHeadline: 'Vivez pleinement.', healthCopy: 'L’esprit, le corps et l’âme. Tout pour vous sentir mieux chaque jour.',
      educationHeadline: 'Le savoir sans limites.', educationCopy: 'Apprenez partout et développez votre potentiel dans chaque domaine.',
      walletHeadline: 'Dépensez, gagnez et retirez.', walletCopy: 'Vos bit$, achats et revenus de créateur vérifiés dans un portefeuille simple.',
      entertainmentHeadline: 'Explorez ce qui vous inspire.', entertainmentCopy: 'Regardez, écoutez, créez et découvrez tout l’univers Beyond.',
      dailyFrench: 'Expression française du jour', dailyVerse: 'Verset biblique du jour', practice: 'Pratiquer →', read: 'Lire et écouter →'
    },
    ht: {
      language: 'Chwazi lang', apps: 'Aplikasyon', health: 'Sante', education: 'Edikasyon', wallet: 'Bous', entertainment: 'Divètisman',
      heroHealth: 'Sante.', heroEducation: 'Edikasyon.', heroWallet: 'Bous.', heroEntertainment: 'Divètisman.',
      tagline: 'Viv. Aprann. Touche. Eksplore.', intro: 'Tout sa ou bezwen pou grandi, kreye epi dekouvri—konekte nan yon sèl ekosistèm.', explore: 'Eksplore ekosistèm nan ▶',
      healthHeadline: 'Viv pi bon lavi ou.', healthCopy: 'Lespri, kò ak nanm. Tout sa ou bezwen pou santi ou pi byen chak jou.',
      educationHeadline: 'Konesans san limit.', educationCopy: 'Aprann nenpòt kote epi devlope kapasite ou nan tout sijè.',
      walletHeadline: 'Depanse, touche epi retire.', walletCopy: 'bit$, acha ak revni kreyatè verifye ou yo nan yon sèl bous fasil.',
      entertainmentHeadline: 'Eksplore sa ki enspire ou.', entertainmentCopy: 'Gade, koute, kreye epi dekouvri nouvo bagay nan linivè Beyond lan.',
      dailyFrench: 'Fraz franse jounen an', dailyVerse: 'Vèsè Bib jounen an', practice: 'Pratike →', read: 'Li epi koute →'
    },
    es: {
      language: 'Elegir idioma', apps: 'Aplicaciones', health: 'Salud', education: 'Educación', wallet: 'Billetera', entertainment: 'Entretenimiento',
      heroHealth: 'Salud.', heroEducation: 'Educación.', heroWallet: 'Billetera.', heroEntertainment: 'Entretenimiento.',
      tagline: 'Vive. Aprende. Gana. Explora.', intro: 'Todo lo que necesitas para crecer, crear y descubrir—conectado en un solo ecosistema.', explore: 'Explorar el ecosistema ▶',
      healthHeadline: 'Vive tu mejor vida.', healthCopy: 'Mente, cuerpo y alma. Todo para sentirte mejor cada día.',
      educationHeadline: 'Conocimiento sin límites.', educationCopy: 'Aprende en cualquier lugar y desarrolla tu potencial en cada materia.',
      walletHeadline: 'Gasta, gana y retira.', walletCopy: 'Tus bit$, compras e ingresos verificados de creador en una sola billetera.',
      entertainmentHeadline: 'Explora lo que te inspira.', entertainmentCopy: 'Mira, escucha, crea y descubre algo nuevo en el universo Beyond.',
      dailyFrench: 'Frase francesa del día', dailyVerse: 'Versículo bíblico del día', practice: 'Practicar →', read: 'Leer y escuchar →'
    }
  };

  var bindings = [
    ['.nav a[href="#health"]','health'], ['.nav a[href="#education"]','education'], ['.nav a[href="#wallet"]','wallet'], ['.nav a[href="#entertainment"]','entertainment'],
    ['.hero h1 .h','heroHealth'], ['.hero h1 .e','heroEducation'], ['.hero h1 .f','heroWallet'], ['.hero h1 .x','heroEntertainment'],
    ['.hero .tagline','tagline'], ['.hero .intro','intro'], ['.hero-actions .ghost','explore'],
    ['.world.health h3','healthHeadline'], ['.world.health .world-copy>p','healthCopy'],
    ['.world.education h3','educationHeadline'], ['.world.education .world-copy>p','educationCopy'],
    ['.world.wallet h3','walletHeadline'], ['.world.wallet .world-copy>p','walletCopy'],
    ['.world.entertainment h3','entertainmentHeadline'], ['.world.entertainment .world-copy>p','entertainmentCopy'],
    ['.daily-demo.french .daily-demo-kicker','dailyFrench'], ['.daily-demo.verse .daily-demo-kicker','dailyVerse'],
    ['.daily-demo.french .daily-demo-action','practice'], ['.daily-demo.verse .daily-demo-action','read']
  ];

  var commonTranslations = {
    fr: {'Home':'Accueil','App Store':'Boutique apps','Apps':'Apps','Sign in':'Connexion','Sign out':'Déconnexion','Create account':'Créer un compte','Dashboard':'Tableau de bord','Profile':'Profil','Settings':'Paramètres','Notifications':'Notifications','Wallet':'Portefeuille','Search':'Rechercher','Learn more':'En savoir plus','Back':'Retour','Save':'Enregistrer','Cancel':'Annuler','Continue':'Continuer','Email':'E-mail','Password':'Mot de passe','Forgot password?':'Mot de passe oublié ?','Remember me':'Se souvenir de moi','Open':'Ouvrir','Launch':'Lancer','Language':'Langue','Theme':'Thème'},
    ht: {'Home':'Akèy','App Store':'Magazen aplikasyon','Apps':'Aplikasyon','Sign in':'Konekte','Sign out':'Dekonekte','Create account':'Kreye kont','Dashboard':'Tablo kontwòl','Profile':'Pwofil','Settings':'Paramèt','Notifications':'Notifikasyon','Wallet':'Bous','Search':'Chèche','Learn more':'Aprann plis','Back':'Retounen','Save':'Sove','Cancel':'Anile','Continue':'Kontinye','Email':'Imèl','Password':'Modpas','Forgot password?':'Ou bliye modpas?','Remember me':'Sonje mwen','Open':'Louvri','Launch':'Lanse','Language':'Lang','Theme':'Tèm'},
    es: {'Home':'Inicio','App Store':'Tienda de apps','Apps':'Aplicaciones','Sign in':'Iniciar sesión','Sign out':'Cerrar sesión','Create account':'Crear cuenta','Dashboard':'Panel','Profile':'Perfil','Settings':'Configuración','Notifications':'Notificaciones','Wallet':'Billetera','Search':'Buscar','Learn more':'Más información','Back':'Volver','Save':'Guardar','Cancel':'Cancelar','Continue':'Continuar','Email':'Correo electrónico','Password':'Contraseña','Forgot password?':'¿Olvidaste tu contraseña?','Remember me':'Recordarme','Open':'Abrir','Launch':'Iniciar','Language':'Idioma','Theme':'Tema'}
  };

  Object.assign(commonTranslations.fr, {
    'Academy':'Académie','TV':'Télé','Games':'Jeux','Marketplace':'Marché','What’s New':'Nouveautés','Beyond Academy':'Académie Beyond',
    'Open learner dashboard':'Ouvrir le tableau de bord','Verify a certificate':'Vérifier un certificat','Browse academies':'Parcourir les académies',
    'Learn it.':'Apprenez.','Prove it.':'Démontrez-le.','Build real skills, complete guided lessons, pass assessments and earn verifiable Beyond-issued certificates.':'Développez de vraies compétences, suivez des leçons guidées, réussissez les évaluations et obtenez des certificats Beyond vérifiables.',
    'Beyond Certificates':'Certificats Beyond','Three pathways. Real evidence of learning.':'Trois parcours. Des preuves concrètes d’apprentissage.',
    'Start pathway':'Commencer le parcours','Live learning':'Apprentissage en direct','Choose your academy':'Choisissez votre académie','Open Academy':'Ouvrir l’académie','Open School':'Ouvrir l’école','Open app':'Ouvrir l’app',
    'Beyond Market':'Marché Beyond','Shop & discover.':'Magasinez et découvrez.','Explore the market':'Explorer le marché','Create with Canvas':'Créer avec Canvas','Start selling':'Commencer à vendre',
    'Search Beyond Market':'Rechercher sur Beyond Market','All categories':'Toutes les catégories','Canvas studio':'Studio Canvas','Live listings':'Annonces en direct','Sell':'Vendre','Original artwork':'Œuvres originales',
    'Find your next favorite thing.':'Trouvez votre prochain coup de cœur.','Browse seller listings':'Voir les annonces','Original Beyond collection':'Collection originale Beyond','Start with artwork you can see.':'Commencez avec des œuvres à découvrir.','Open Canvas':'Ouvrir Canvas',
    'Featured creator experiences':'Expériences créatives en vedette','Artwork, releases and services':'Œuvres, nouveautés et services','Buy now & auction':'Achat immédiat et enchères','Live seller listings':'Annonces vendeurs en direct','View all listings':'Voir toutes les annonces',
    'Create & earn':'Créer et gagner','Seller tools':'Outils vendeurs','Fresh from Beyond Market.':'Nouveautés du Marché Beyond.','Open Marketplace →':'Ouvrir le marché →','See the full seller floor →':'Voir tout l’espace vendeur →',
    'PLAYABLE NOW · BEYOND GAMES':'JOUABLE MAINTENANT · BEYOND GAMES','Live demo games.':'Démos de jeux en direct.','Explore Beyond Games →':'Explorer Beyond Games →','Play demo →':'Jouer à la démo →','Game details':'Détails du jeu',
    'Physical':'Physique','Digital':'Numérique','Live listing':'Annonce en direct','Buy Now':'Acheter','Preview in Canvas →':'Aperçu dans Canvas →','Customize in Canvas':'Personnaliser dans Canvas','Quick view':'Aperçu rapide','Save to watchlist':'Enregistrer',
    'Every Beyond app.':'Toutes les apps Beyond.','One store.':'Une seule boutique.','List something new.':'Publiez une nouveauté.','Publish through Beyond Sell':'Publier avec Beyond Sell'
  });
  Object.assign(commonTranslations.ht, {
    'Academy':'Akademi','TV':'Televizyon','Games':'Jwèt','Marketplace':'Mache','What’s New':'Sa ki nouvo','Beyond Academy':'Akademi Beyond',
    'Open learner dashboard':'Louvri tablo elèv la','Verify a certificate':'Verifye yon sètifika','Browse academies':'Gade akademi yo',
    'Learn it.':'Aprann li.','Prove it.':'Pwouve li.','Build real skills, complete guided lessons, pass assessments and earn verifiable Beyond-issued certificates.':'Devlope bonjan ladrès, fini leson gide yo, pase evalyasyon epi resevwa sètifika Beyond ki ka verifye.',
    'Beyond Certificates':'Sètifika Beyond','Three pathways. Real evidence of learning.':'Twa chemen. Prèv reyèl ou aprann.',
    'Start pathway':'Kòmanse chemen an','Live learning':'Aprantisaj an dirèk','Choose your academy':'Chwazi akademi ou','Open Academy':'Louvri akademi','Open School':'Louvri lekòl','Open app':'Louvri aplikasyon',
    'Beyond Market':'Mache Beyond','Shop & discover.':'Achte epi dekouvri.','Explore the market':'Eksplore mache a','Create with Canvas':'Kreye ak Canvas','Start selling':'Kòmanse vann',
    'Search Beyond Market':'Chèche nan Mache Beyond','All categories':'Tout kategori','Canvas studio':'Estidyo Canvas','Live listings':'Anons an dirèk','Sell':'Vann','Original artwork':'Zèv orijinal',
    'Find your next favorite thing.':'Jwenn pwochen bagay ou renmen an.','Browse seller listings':'Gade anons vandè yo','Original Beyond collection':'Koleksyon orijinal Beyond','Start with artwork you can see.':'Kòmanse ak zèv ou ka wè.','Open Canvas':'Louvri Canvas',
    'Featured creator experiences':'Eksperyans kreyatè vedèt','Artwork, releases and services':'Zèv, nouvo sòti ak sèvis','Buy now & auction':'Achte kounye a ak vann piblik','Live seller listings':'Anons vandè an dirèk','View all listings':'Gade tout anons yo',
    'Create & earn':'Kreye epi touche','Seller tools':'Zouti vandè','Fresh from Beyond Market.':'Nouvo nan Mache Beyond.','Open Marketplace →':'Louvri mache a →','See the full seller floor →':'Gade tout espas vandè a →',
    'PLAYABLE NOW · BEYOND GAMES':'JWE KOUNYE A · BEYOND GAMES','Live demo games.':'Demo jwèt an dirèk.','Explore Beyond Games →':'Eksplore Beyond Games →','Play demo →':'Jwe demo a →','Game details':'Detay jwèt la',
    'Physical':'Fizik','Digital':'Dijital','Live listing':'Anons an dirèk','Buy Now':'Achte kounye a','Preview in Canvas →':'Gade nan Canvas →','Customize in Canvas':'Pèsonalize nan Canvas','Quick view':'Gade rapid','Save to watchlist':'Sove',
    'Every Beyond app.':'Tout aplikasyon Beyond.','One store.':'Yon sèl magazen.','List something new.':'Mete yon nouvo bagay.','Publish through Beyond Sell':'Pibliye ak Beyond Sell'
  });
  Object.assign(commonTranslations.es, {
    'Academy':'Academia','TV':'TV','Games':'Juegos','Marketplace':'Mercado','What’s New':'Novedades','Beyond Academy':'Academia Beyond',
    'Open learner dashboard':'Abrir panel del estudiante','Verify a certificate':'Verificar un certificado','Browse academies':'Explorar academias',
    'Learn it.':'Apréndelo.','Prove it.':'Demuéstralo.','Build real skills, complete guided lessons, pass assessments and earn verifiable Beyond-issued certificates.':'Desarrolla habilidades reales, completa lecciones guiadas, aprueba evaluaciones y obtén certificados Beyond verificables.',
    'Beyond Certificates':'Certificados Beyond','Three pathways. Real evidence of learning.':'Tres rutas. Evidencia real de aprendizaje.',
    'Start pathway':'Comenzar ruta','Live learning':'Aprendizaje en vivo','Choose your academy':'Elige tu academia','Open Academy':'Abrir academia','Open School':'Abrir escuela','Open app':'Abrir app',
    'Beyond Market':'Mercado Beyond','Shop & discover.':'Compra y descubre.','Explore the market':'Explorar el mercado','Create with Canvas':'Crear con Canvas','Start selling':'Empezar a vender',
    'Search Beyond Market':'Buscar en Beyond Market','All categories':'Todas las categorías','Canvas studio':'Estudio Canvas','Live listings':'Anuncios en vivo','Sell':'Vender','Original artwork':'Arte original',
    'Find your next favorite thing.':'Encuentra tu próximo favorito.','Browse seller listings':'Ver anuncios','Original Beyond collection':'Colección original Beyond','Start with artwork you can see.':'Empieza con arte que puedes ver.','Open Canvas':'Abrir Canvas',
    'Featured creator experiences':'Experiencias destacadas','Artwork, releases and services':'Arte, novedades y servicios','Buy now & auction':'Compra directa y subasta','Live seller listings':'Anuncios de vendedores en vivo','View all listings':'Ver todos los anuncios',
    'Create & earn':'Crea y gana','Seller tools':'Herramientas para vendedores','Fresh from Beyond Market.':'Lo nuevo de Beyond Market.','Open Marketplace →':'Abrir mercado →','See the full seller floor →':'Ver todo el espacio de vendedores →',
    'PLAYABLE NOW · BEYOND GAMES':'JUEGA AHORA · BEYOND GAMES','Live demo games.':'Demos de juegos en vivo.','Explore Beyond Games →':'Explorar Beyond Games →','Play demo →':'Jugar demo →','Game details':'Detalles del juego',
    'Physical':'Físico','Digital':'Digital','Live listing':'Anuncio en vivo','Buy Now':'Comprar ahora','Preview in Canvas →':'Vista previa en Canvas →','Customize in Canvas':'Personalizar en Canvas','Quick view':'Vista rápida','Save to watchlist':'Guardar',
    'Every Beyond app.':'Todas las apps Beyond.','One store.':'Una sola tienda.','List something new.':'Publica algo nuevo.','Publish through Beyond Sell':'Publicar con Beyond Sell'
  });

  var textSources = typeof WeakMap === 'function' ? new WeakMap() : null;

  function translateCommon(locale) {
    var translations = commonTranslations[locale] || {};
    var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    var textNodes = [];
    var current;
    while ((current = walker.nextNode())) textNodes.push(current);
    for (var index = 0; index < textNodes.length; index += 1) {
      var textNode = textNodes[index];
      var parent = textNode.parentElement;
      if (!parent || parent.closest('script,style,code,pre,[data-no-translate],[contenteditable="true"]')) continue;
      var sourceText = textSources && textSources.has(textNode) ? textSources.get(textNode) : textNode.nodeValue;
      if (textSources && !textSources.has(textNode)) textSources.set(textNode, sourceText);
      var sourceTrimmed = (sourceText || '').trim();
      if (!sourceTrimmed) continue;
      var translated = locale === 'en' ? sourceTrimmed : (translations[sourceTrimmed] || sourceTrimmed);
      textNode.nodeValue = sourceText.replace(sourceTrimmed, translated);
    }
    var placeholders = document.querySelectorAll('input[placeholder],textarea[placeholder]');
    for (var item = 0; item < placeholders.length; item += 1) {
      var field = placeholders[item];
      var source = field.getAttribute('data-i18n-placeholder') || field.getAttribute('placeholder');
      if (!field.hasAttribute('data-i18n-placeholder')) field.setAttribute('data-i18n-placeholder', source);
      field.setAttribute('placeholder', locale === 'en' ? source : (translations[source] || source));
    }
    var attributed = document.querySelectorAll('[aria-label],[title]');
    for (var attributeIndex = 0; attributeIndex < attributed.length; attributeIndex += 1) {
      var element = attributed[attributeIndex];
      ['aria-label', 'title'].forEach(function (attribute) {
        if (!element.hasAttribute(attribute)) return;
        var sourceAttribute = 'data-i18n-' + attribute;
        var original = element.getAttribute(sourceAttribute) || element.getAttribute(attribute);
        if (!element.hasAttribute(sourceAttribute)) element.setAttribute(sourceAttribute, original);
        element.setAttribute(attribute, locale === 'en' ? original : (translations[original] || original));
      });
    }
  }

  function apply(locale) {
    var dictionary = dictionaries[locale] || dictionaries.en;
    var appStoreLabels = { en: 'App Store', fr: 'Boutique apps', ht: 'Magazen aplikasyon', es: 'Tienda de apps' };
    var appStoreCtas = { en: 'Open the App Store ▶', fr: 'Ouvrir la boutique ▶', ht: 'Louvri magazen an ▶', es: 'Abrir la tienda ▶' };
    var root = document.documentElement;
    if (!root) return;
    root.lang = locale;
    root.dataset.locale = locale;
    bindings.forEach(function (binding) {
      document.querySelectorAll(binding[0]).forEach(function (node) { var value = dictionary[binding[1]]; if (node.textContent !== value) node.textContent = value; });
    });
    document.querySelectorAll('.bos-apps-toggle').forEach(function (button) { var value = dictionary.apps + ' ▾'; if (button.textContent !== value) button.textContent = value; });
    document.querySelectorAll('.bos-locale').forEach(function (label) { label.title = dictionary.language; });
    document.querySelectorAll('#localePicker').forEach(function (picker) { picker.setAttribute('aria-label', dictionary.language); });
    document.querySelectorAll('.bos-app-store-label-full').forEach(function (label) { var value = appStoreLabels[locale] || appStoreLabels.en; if (label.textContent !== value) label.textContent = value; });
    document.querySelectorAll('.hero-actions .ghost').forEach(function (link) { var value = appStoreCtas[locale] || appStoreCtas.en; if (link.textContent !== value) link.textContent = value; });
    document.querySelectorAll('#beyond-os-shell .bos-home-label').forEach(function (label) { if (label.textContent !== 'BEYOND OS') label.textContent = 'BEYOND OS'; });
    document.querySelectorAll('.bos-kicker,.bos-hero h1,.os,.logo').forEach(function (label) {
      var nextText = label.textContent
        .replace(/Beyond OS 2\.1 Beta/gi, 'Beyond OS · Beta')
        .replace(/(Beyond (?:Wallet|Investing|TV|Sell|Finance|Careers)) (?:2\.1|2\.2) Beta/gi, '$1 · Beta Build 2.1.1');
      if (label.textContent !== nextText) label.textContent = nextText;
    });
    translateCommon(locale);
  }

  function selectedLocale() {
    try { return localStorage.getItem('beyond-locale') || 'en'; } catch (error) { return 'en'; }
  }

  document.addEventListener('beyond:locale-change', function (event) { apply(event.detail && event.detail.locale || 'en'); });
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { apply(selectedLocale()); });
  else apply(selectedLocale());
  if (typeof MutationObserver === 'function') {
    var localeRefresh = 0;
    var observeLocaleRoot = function () {
      var root = document.documentElement;
      if (!root) return;
      new MutationObserver(function () {
        clearTimeout(localeRefresh);
        localeRefresh = setTimeout(function () { apply(selectedLocale()); }, 50);
      }).observe(root, { childList: true, subtree: true });
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', observeLocaleRoot, { once: true });
    else observeLocaleRoot();
  }
})();
