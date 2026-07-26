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

  function translateCommon(locale) {
    var translations = commonTranslations[locale];
    if (!translations) {
      document.querySelectorAll('[data-i18n-source]').forEach(function (node) { node.textContent = node.getAttribute('data-i18n-source'); });
      document.querySelectorAll('[data-i18n-placeholder]').forEach(function (field) { field.setAttribute('placeholder', field.getAttribute('data-i18n-placeholder')); });
      return;
    }
    var nodes = document.querySelectorAll('a,button,label,h1,h2,h3,h4,p,span,strong,small,option');
    for (var index = 0; index < nodes.length; index += 1) {
      var node = nodes[index];
      if (node.children.length !== 0 || (node.closest && node.closest('[data-no-translate]'))) continue;
      var original = node.getAttribute('data-i18n-source') || node.textContent.trim();
      if (!translations[original]) continue;
      if (!node.hasAttribute('data-i18n-source')) node.setAttribute('data-i18n-source', original);
      node.textContent = translations[original];
    }
    var placeholders = document.querySelectorAll('input[placeholder],textarea[placeholder]');
    for (var item = 0; item < placeholders.length; item += 1) {
      var field = placeholders[item];
      var source = field.getAttribute('data-i18n-placeholder') || field.getAttribute('placeholder');
      if (!translations[source]) continue;
      if (!field.hasAttribute('data-i18n-placeholder')) field.setAttribute('data-i18n-placeholder', source);
      field.setAttribute('placeholder', translations[source]);
    }
  }

  function apply(locale) {
    var dictionary = dictionaries[locale] || dictionaries.en;
    var appStoreLabels = { en: 'App Store', fr: 'Boutique apps', ht: 'Magazen aplikasyon', es: 'Tienda de apps' };
    var appStoreCtas = { en: 'Open the App Store ▶', fr: 'Ouvrir la boutique ▶', ht: 'Louvri magazen an ▶', es: 'Abrir la tienda ▶' };
    document.documentElement.lang = locale;
    document.documentElement.dataset.locale = locale;
    bindings.forEach(function (binding) {
      document.querySelectorAll(binding[0]).forEach(function (node) { node.textContent = dictionary[binding[1]]; });
    });
    document.querySelectorAll('.bos-apps-toggle').forEach(function (button) { button.textContent = dictionary.apps + ' ▾'; });
    document.querySelectorAll('.bos-locale').forEach(function (label) { label.title = dictionary.language; });
    document.querySelectorAll('#localePicker').forEach(function (picker) { picker.setAttribute('aria-label', dictionary.language); });
    document.querySelectorAll('.bos-app-store-label-full').forEach(function (label) { label.textContent = appStoreLabels[locale] || appStoreLabels.en; });
    document.querySelectorAll('.hero-actions .ghost').forEach(function (link) { link.textContent = appStoreCtas[locale] || appStoreCtas.en; });
    document.querySelectorAll('#beyond-os-shell .bos-home-label').forEach(function (label) { label.textContent = 'BEYOND OS 2.3'; });
    document.querySelectorAll('.bos-kicker,.bos-hero h1,.os,.logo').forEach(function (label) {
      label.textContent = label.textContent
        .replace(/Beyond OS 2\.1 Beta/gi, 'Beyond OS · Beta Build 2.1.1')
        .replace(/(Beyond (?:Wallet|Investing|TV|Sell|Finance|Careers)) (?:2\.1|2\.2) Beta/gi, '$1 · Beta Build 2.1.1');
    });
    translateCommon(locale);
  }

  function selectedLocale() {
    try { return localStorage.getItem('beyond-locale') || 'en'; } catch (error) { return 'en'; }
  }

  document.addEventListener('beyond:locale-change', function (event) { apply(event.detail && event.detail.locale || 'en'); });
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { apply(selectedLocale()); });
  else apply(selectedLocale());
})();
