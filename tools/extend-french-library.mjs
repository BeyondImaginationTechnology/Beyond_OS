import { readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const lessonsPath = resolve(root, 'beyond-french/data/lessons.json');
const dictionaryPath = resolve(root, 'beyond-french/data/dictionary.json');

// english | french | pronunciation | spanish | kreyol | patois | category | module
const source = `
Welcome!|Bienvenue !|Byan-vuh-new|¡Bienvenido!|Byenveni!|Welcome!|Greetings|greetings
Good afternoon.|Bon après-midi.|Bohn ah-preh mee-dee|Buenas tardes.|Bon aprèmidi.|Good afternoon.|Greetings|greetings
Sleep well.|Dors bien.|Dor byan|Duerme bien.|Dòmi byen.|Sleep good.|Greetings|greetings
Have a nice weekend.|Bon week-end.|Bohn week-end|Que tengas un buen fin de semana.|Pase yon bon wikenn.|Have a nice weekend.|Greetings|greetings
It is good to see you.|Ça fait plaisir de te voir.|Sah feh pleh-zeer duh tuh vwar|Me alegra verte.|Mwen kontan wè ou.|Good fi see yuh.|Greetings|greetings
How have you been?|Comment vas-tu depuis ?|Koh-mahn vah-tew duh-pwee|¿Cómo has estado?|Kijan ou te ye?|How yuh been?|Greetings|greetings
Please come in.|Entrez, s’il vous plaît.|Ahn-tray seel voo pleh|Pase, por favor.|Antre, tanpri.|Come een, please.|Greetings|greetings
Make yourself at home.|Fais comme chez toi.|Feh kohm shay twah|Siéntete como en casa.|Fè tankou ou lakay ou.|Mek yuhself at home.|Greetings|greetings
Give me a call.|Appelle-moi.|Ah-pell mwah|Llámame.|Rele mwen.|Call mi.|Greetings|greetings
See you next week.|À la semaine prochaine.|Ah lah suh-men proh-shen|Nos vemos la próxima semana.|N a wè semèn pwochèn.|See yuh next week.|Greetings|greetings
What do you mean?|Qu’est-ce que tu veux dire ?|Kess kuh tew vuh deer|¿Qué quieres decir?|Kisa ou vle di?|Wah yuh mean?|Conversation|greetings
I have a question.|J’ai une question.|Zhay ewn kes-tyohn|Tengo una pregunta.|Mwen gen yon kesyon.|Mi have a question.|Conversation|greetings
That is interesting.|C’est intéressant.|Set an-tay-ray-sahn|Eso es interesante.|Sa enteresan.|Dat interesting.|Conversation|greetings
I think so.|Je pense que oui.|Zhuh pahns kuh wee|Creo que sí.|Mwen panse wi.|Mi tink so.|Conversation|greetings
I do not think so.|Je ne pense pas.|Zhuh nuh pahns pah|No lo creo.|Mwen pa panse sa.|Mi nuh tink so.|Conversation|greetings
That is a good idea.|C’est une bonne idée.|Set ewn bun ee-day|Es una buena idea.|Sa se yon bon lide.|Dat a good idea.|Conversation|greetings
I am not sure.|Je ne suis pas sûr.|Zhuh nuh swee pah sewr|No estoy seguro.|Mwen pa sèten.|Mi nuh sure.|Conversation|greetings
Can you explain it?|Peux-tu l’expliquer ?|Puh-tew lex-plee-kay|¿Puedes explicarlo?|Èske ou ka esplike li?|Can yuh explain it?|Conversation|greetings
What happened?|Qu’est-ce qui s’est passé ?|Kess kee say pah-say|¿Qué pasó?|Kisa ki te pase?|Wah happen?|Conversation|greetings
Everything is fine.|Tout va bien.|Too vah byan|Todo está bien.|Tout bagay anfòm.|Everyting good.|Conversation|greetings
I am listening.|Je t’écoute.|Zhuh tay-koot|Te escucho.|Mwen ap koute ou.|Mi a listen.|Conversation|greetings
Tell me more.|Dis-m’en plus.|Dee mahn plew|Cuéntame más.|Di mwen plis.|Tell mi more.|Conversation|greetings
Could you write it down?|Pouvez-vous l’écrire ?|Poo-vay voo lay-kreer|¿Puede escribirlo?|Èske ou ka ekri li?|Can yuh write it down?|Conversation|greetings
How do you say this in French?|Comment dit-on cela en français ?|Koh-mahn dee-tohn suh-lah ahn frahn-say|¿Cómo se dice esto en francés?|Kijan yo di sa an franse?|How yuh say dis in French?|Conversation|greetings
What does this word mean?|Que veut dire ce mot ?|Kuh vuh deer suh moh|¿Qué significa esta palabra?|Kisa mo sa vle di?|Wah dis word mean?|Conversation|greetings
I am learning French.|J’apprends le français.|Zhah-prahn luh frahn-say|Estoy aprendiendo francés.|Mwen ap aprann franse.|Mi a learn French.|Conversation|greetings
I need more practice.|J’ai besoin de plus de pratique.|Zhay buh-zwan duh plew duh prah-teek|Necesito más práctica.|Mwen bezwen plis pratik.|Mi need more practice.|Conversation|greetings
Please correct me.|Corrigez-moi, s’il vous plaît.|Koh-ree-zhay mwah seel voo pleh|Corríjame, por favor.|Korije mwen, tanpri.|Correct mi, please.|Conversation|greetings
Let me think.|Laisse-moi réfléchir.|Less mwah ray-flay-sheer|Déjame pensar.|Kite mwen reflechi.|Mek mi tink.|Conversation|greetings
I will try again.|Je vais réessayer.|Zhuh vay ray-eh-say-yay|Lo intentaré de nuevo.|Mwen pral eseye ankò.|Mi ago try again.|Conversation|greetings
Where is the train station?|Où est la gare ?|Oo eh lah gahr|¿Dónde está la estación de tren?|Ki kote estasyon tren an ye?|Weh di train station deh?|Travel|transport-travel
I need a taxi.|J’ai besoin d’un taxi.|Zhay buh-zwan dun tak-see|Necesito un taxi.|Mwen bezwen yon taksi.|Mi need a taxi.|Travel|transport-travel
Please take me to this address.|Conduisez-moi à cette adresse, s’il vous plaît.|Kohn-dwee-zay mwah ah set ah-dress seel voo pleh|Lléveme a esta dirección, por favor.|Mennen mwen nan adrès sa a, tanpri.|Carry mi to dis address, please.|Travel|transport-travel
How long does it take?|Combien de temps faut-il ?|Kohm-byan duh tahn foh-teel|¿Cuánto tiempo tarda?|Konbyen tan sa pran?|How long it tek?|Travel|transport-travel
Is it far from here?|C’est loin d’ici ?|Say lwan dee-see|¿Está lejos de aquí?|Èske li lwen isit la?|It far from yah?|Travel|transport-travel
Turn left.|Tournez à gauche.|Toor-nay ah gosh|Gire a la izquierda.|Vire agoch.|Turn lef.|Travel|transport-travel
Turn right.|Tournez à droite.|Toor-nay ah drwat|Gire a la derecha.|Vire adwat.|Turn right.|Travel|transport-travel
Go straight ahead.|Allez tout droit.|Ah-lay too drwah|Siga todo recto.|Ale tou dwat.|Go straight.|Travel|transport-travel
I am lost.|Je suis perdu.|Zhuh swee pair-dew|Estoy perdido.|Mwen pèdi.|Mi lost.|Travel|transport-travel
Can you show me on the map?|Pouvez-vous me montrer sur la carte ?|Poo-vay voo muh mohn-tray sewr lah kart|¿Puede mostrarme en el mapa?|Èske ou ka montre mwen sou kat la?|Show mi pon di map?|Travel|transport-travel
What time does the train leave?|À quelle heure part le train ?|Ah kell uhr par luh tran|¿A qué hora sale el tren?|A kilè tren an pati?|Wah time di train leave?|Travel|transport-travel
I would like a ticket.|Je voudrais un billet.|Zhuh voo-dray uhn bee-yay|Quisiera un boleto.|Mwen ta renmen yon tikè.|Mi want a ticket.|Travel|transport-travel
One-way or round trip?|Aller simple ou aller-retour ?|Ah-lay sam-pl oo ah-lay ruh-toor|¿Solo ida o ida y vuelta?|Ale senp oswa ale-retou?|One way or round trip?|Travel|transport-travel
Where can I buy a ticket?|Où puis-je acheter un billet ?|Oo pwee-zh ash-tay uhn bee-yay|¿Dónde puedo comprar un boleto?|Ki kote mwen ka achte yon tikè?|Weh mi can buy a ticket?|Travel|transport-travel
Is this seat free?|Cette place est libre ?|Set plahs eh leebr|¿Está libre este asiento?|Èske plas sa a lib?|Dis seat free?|Travel|transport-travel
Please wake me at seven.|Réveillez-moi à sept heures.|Ray-vay-yay mwah ah set uhr|Despiérteme a las siete.|Reveye mwen a setè.|Wake mi at seven, please.|Travel|transport-travel
I have a reservation.|J’ai une réservation.|Zhay ewn ray-zair-vah-syohn|Tengo una reservación.|Mwen gen yon rezèvasyon.|Mi have a reservation.|Travel|transport-travel
Where is my hotel?|Où est mon hôtel ?|Oo eh mohn oh-tel|¿Dónde está mi hotel?|Ki kote otèl mwen an ye?|Weh mi hotel deh?|Travel|transport-travel
I need a room for one night.|J’ai besoin d’une chambre pour une nuit.|Zhay buh-zwan dewn shahm-bruh poor ewn nwee|Necesito una habitación por una noche.|Mwen bezwen yon chanm pou yon nwit.|Mi need a room fi one night.|Travel|transport-travel
What is the Wi-Fi password?|Quel est le mot de passe du Wi-Fi ?|Kell eh luh moh duh pahs dew wee-fee|¿Cuál es la contraseña del Wi-Fi?|Ki modpas Wi-Fi a?|Wah di Wi-Fi password?|Travel|transport-travel
A table for two, please.|Une table pour deux, s’il vous plaît.|Ewn tahbl poor duh seel voo pleh|Una mesa para dos, por favor.|Yon tab pou de, tanpri.|Table fi two, please.|Food|food
May I see the menu?|Puis-je voir le menu ?|Pwee-zh vwar luh muh-new|¿Puedo ver el menú?|Èske mwen ka wè meni an?|Can mi see di menu?|Food|food
What do you recommend?|Qu’est-ce que vous recommandez ?|Kess kuh voo ruh-koh-mahn-day|¿Qué recomienda?|Kisa ou rekòmande?|Wah yuh recommend?|Food|food
I am vegetarian.|Je suis végétarien.|Zhuh swee vay-zhay-tah-ryan|Soy vegetariano.|Mwen vejetaryen.|Mi a vegetarian.|Food|food
I am allergic to nuts.|Je suis allergique aux noix.|Zhuh swee zah-lair-zheek oh nwah|Soy alérgico a las nueces.|Mwen alèjik ak nwa.|Mi allergic to nuts.|Food|food
No sugar, please.|Sans sucre, s’il vous plaît.|Sahn sew-kruh seel voo pleh|Sin azúcar, por favor.|San sik, tanpri.|No sugar, please.|Food|food
I would like a coffee.|Je voudrais un café.|Zhuh voo-dray uhn kah-fay|Quisiera un café.|Mwen ta renmen yon kafe.|Mi want a coffee.|Food|food
The bill, please.|L’addition, s’il vous plaît.|Lah-dee-syohn seel voo pleh|La cuenta, por favor.|Bòdwo a, tanpri.|Di bill, please.|Food|food
This is delicious.|C’est délicieux.|Say day-lee-syuh|Esto está delicioso.|Sa bon anpil.|Dis delicious.|Food|food
It is too spicy.|C’est trop épicé.|Say troh ay-pee-say|Está demasiado picante.|Li twò pike.|It too spicy.|Food|food
I would like it well done.|Je le voudrais bien cuit.|Zhuh luh voo-dray byan kwee|Lo quisiera bien cocido.|Mwen ta renmen li byen kwit.|Mi want it well done.|Food|food
Can I have more bread?|Puis-je avoir plus de pain ?|Pwee-zh ah-vwar plew duh pan|¿Puedo tener más pan?|Èske mwen ka jwenn plis pen?|Can mi get more bread?|Food|food
Cheers!|Santé !|Sahn-tay|¡Salud!|Lasante!|Cheers!|Food|food
I am full.|Je n’ai plus faim.|Zhuh nay plew fan|Estoy lleno.|Mwen plen.|Mi belly full.|Food|food
Breakfast is included.|Le petit-déjeuner est compris.|Luh puh-tee day-zhuh-nay eh kohm-pree|El desayuno está incluido.|Dejene a ladan.|Breakfast include.|Food|food
Do you have this in another size?|Vous avez ceci dans une autre taille ?|Voo zah-vay suh-see dahn zewn oh-truh tie|¿Tiene esto en otra talla?|Èske ou gen sa nan yon lòt gwosè?|Yuh have dis in anodda size?|Shopping|everyday
Can I try it on?|Je peux l’essayer ?|Zhuh puh lay-say-yay|¿Puedo probármelo?|Èske mwen ka eseye li?|Can mi try it on?|Shopping|everyday
It is too expensive.|C’est trop cher.|Say troh shair|Es demasiado caro.|Li twò chè.|It too dear.|Shopping|everyday
Do you have anything cheaper?|Vous avez quelque chose de moins cher ?|Voo zah-vay kell-kuh shohz duh mwan shair|¿Tiene algo más barato?|Èske ou gen yon bagay pi bon mache?|Yuh have anything cheaper?|Shopping|everyday
I am just looking.|Je regarde seulement.|Zhuh ruh-gard suhl-mahn|Solo estoy mirando.|Mwen ap gade sèlman.|Mi just a look.|Shopping|everyday
I will take it.|Je le prends.|Zhuh luh prahn|Me lo llevo.|Mwen ap pran li.|Mi tek it.|Shopping|everyday
Can I pay in cash?|Je peux payer en espèces ?|Zhuh puh pay-yay ahn es-pess|¿Puedo pagar en efectivo?|Èske mwen ka peye kach?|Can mi pay cash?|Shopping|everyday
May I have a receipt?|Puis-je avoir un reçu ?|Pwee-zh ah-vwar uhn ruh-sew|¿Me da un recibo?|Èske mwen ka jwenn yon resi?|Can mi get a receipt?|Shopping|everyday
Where is the market?|Où est le marché ?|Oo eh luh mar-shay|¿Dónde está el mercado?|Ki kote mache a ye?|Weh di market deh?|Shopping|everyday
What time do you close?|À quelle heure fermez-vous ?|Ah kell uhr fair-may voo|¿A qué hora cierran?|A kilè nou fèmen?|Wah time unu close?|Shopping|everyday
I am ready.|Je suis prêt.|Zhuh swee preh|Estoy listo.|Mwen pare.|Mi ready.|Everyday|everyday
I am tired.|Je suis fatigué.|Zhuh swee fah-tee-gay|Estoy cansado.|Mwen fatige.|Mi tired.|Everyday|everyday
I am hungry.|J’ai faim.|Zhay fan|Tengo hambre.|Mwen grangou.|Mi hungry.|Everyday|everyday
I am thirsty.|J’ai soif.|Zhay swaf|Tengo sed.|Mwen swaf.|Mi thirsty.|Everyday|everyday
I am busy right now.|Je suis occupé en ce moment.|Zhuh swee zoh-kew-pay ahn suh moh-mahn|Estoy ocupado ahora.|Mwen okipe kounye a.|Mi busy right now.|Everyday|everyday
I will be there soon.|Je serai bientôt là.|Zhuh suh-ray byan-toh lah|Estaré allí pronto.|Mwen ap la talè.|Mi soon reach.|Everyday|everyday
Wait a moment, please.|Attendez un moment, s’il vous plaît.|Ah-tahn-day uhn moh-mahn seel voo pleh|Espere un momento, por favor.|Tann yon ti moman, tanpri.|Wait likkle, please.|Everyday|everyday
I have to go.|Je dois partir.|Zhuh dwah par-teer|Tengo que irme.|Mwen dwe ale.|Mi haffi go.|Everyday|everyday
What time is it?|Quelle heure est-il ?|Kell uhr eh-teel|¿Qué hora es?|Ki lè li ye?|Wah time it is?|Everyday|everyday
It is raining.|Il pleut.|Eel pluh|Está lloviendo.|Lapli ap tonbe.|Rain a fall.|Everyday|everyday
The weather is beautiful.|Il fait beau.|Eel feh boh|Hace buen tiempo.|Tan an bèl.|Di weather nice.|Everyday|everyday
Open the window, please.|Ouvrez la fenêtre, s’il vous plaît.|Oo-vray lah fuh-net-ruh seel voo pleh|Abra la ventana, por favor.|Louvri fenèt la, tanpri.|Open di window, please.|Everyday|everyday
Close the door, please.|Fermez la porte, s’il vous plaît.|Fair-may lah port seel voo pleh|Cierre la puerta, por favor.|Fèmen pòt la, tanpri.|Close di door, please.|Everyday|everyday
I am looking for my keys.|Je cherche mes clés.|Zhuh shairsh may klay|Estoy buscando mis llaves.|Mwen ap chèche kle mwen.|Mi a look fi mi keys.|Everyday|everyday
My phone is not working.|Mon téléphone ne marche pas.|Mohn tay-lay-fon nuh marsh pah|Mi teléfono no funciona.|Telefòn mwen pa mache.|Mi phone nuh work.|Everyday|everyday
Believe in yourself.|Crois en toi.|Krwah zahn twah|Cree en ti.|Kwè nan tèt ou.|Believe in yuhself.|Encouragement|sports
You are doing great.|Tu te débrouilles très bien.|Tew tuh day-broo-yuh treh byan|Lo estás haciendo muy bien.|Ou ap fè trè byen.|Yuh a do great.|Encouragement|sports
One step at a time.|Un pas à la fois.|Uhn pah ah lah fwah|Un paso a la vez.|Yon pa alafwa.|One step at a time.|Encouragement|sports
Do not be afraid.|N’aie pas peur.|Nay pah puhr|No tengas miedo.|Pa pè.|Nuh fraid.|Encouragement|sports
Stay positive.|Reste positif.|Rest poh-zee-teef|Mantente positivo.|Rete pozitif.|Stay positive.|Encouragement|sports
You have made progress.|Tu as fait des progrès.|Tew ah feh day proh-greh|Has progresado.|Ou fè pwogrè.|Yuh make progress.|Encouragement|sports
Mistakes help us learn.|Les erreurs nous aident à apprendre.|Lay zair-uhr noo zed ah ah-prahn-druh|Los errores nos ayudan a aprender.|Erè ede nou aprann.|Mistakes help wi learn.|Encouragement|sports
Keep practicing.|Continue à t’entraîner.|Kohn-tee-new ah tahn-tray-nay|Sigue practicando.|Kontinye pratike.|Keep practicing.|Encouragement|sports
I am proud of my progress.|Je suis fier de mes progrès.|Zhuh swee fee-air duh may proh-greh|Estoy orgulloso de mi progreso.|Mwen fyè de pwogrè mwen.|Mi proud a mi progress.|Encouragement|sports
Today is a new opportunity.|Aujourd’hui est une nouvelle occasion.|Oh-zhoor-dwee eh tewn noo-vell oh-kah-zyohn|Hoy es una nueva oportunidad.|Jodi a se yon nouvo opòtinite.|Today a new opportunity.|Encouragement|sports
`;

const rows = source.trim().split('\n').map((line, index) => {
  const fields = line.split('|').map((value) => value.trim());
  if (fields.length !== 8) throw new Error(`Row ${index + 1} has ${fields.length} fields`);
  const [english, french, pronunciation, spanish, kreyol, patois, category, module] = fields;
  return { english, french, pronunciation, spanish, kreyol, patois, category, module };
});

if (rows.length !== 100) throw new Error(`Expected 100 entries, found ${rows.length}`);

const lessons = JSON.parse(await readFile(lessonsPath, 'utf8'));
const dictionary = JSON.parse(await readFile(dictionaryPath, 'utf8'));
const retainedLessons = lessons.filter((lesson) => !lesson.generated_batch || lesson.generated_batch !== 'azure-2026-08');
const retainedDictionary = dictionary.filter((word) => !word.generated_batch || word.generated_batch !== 'azure-2026-08');
const maxId = retainedLessons.reduce((max, lesson) => Math.max(max, Number(lesson.id) || 0), 0);
const startDate = new Date('2026-08-16T12:00:00Z');

const newLessons = rows.map((row, index) => {
  const date = new Date(startDate);
  date.setUTCDate(date.getUTCDate() + index);
  return {
    id: maxId + index + 1,
    date: date.toISOString().slice(0, 10),
    category: row.category,
    module: row.module,
    english: row.english,
    french: row.french,
    french_pronunciation: row.pronunciation,
    patois: row.patois,
    patois_pronunciation: row.patois,
    kreyol: row.kreyol,
    kreyol_pronunciation: row.kreyol,
    spanish: row.spanish,
    spanish_pronunciation: row.spanish,
    meaning: `A practical ${row.category.toLowerCase()} phrase for confident everyday conversation.`,
    culture_note: 'Practice this phrase aloud, then use it in a short conversation of your own.',
    challenge: `How would you say “${row.english}” in French?`,
    answer: row.french,
    audio_url: '',
    generated_batch: 'azure-2026-08',
  };
});

const newDictionary = rows.map((row, index) => ({
  english: row.english.replace(/[.!?]+$/, '').toLocaleLowerCase('en'),
  french: row.french,
  pronunciation: row.pronunciation,
  spanish: row.spanish,
  kreyol: row.kreyol,
  patois: row.patois,
  type: row.category.toLocaleLowerCase('en'),
  lesson_id: maxId + index + 1,
  audio_url: '',
  generated_batch: 'azure-2026-08',
}));

await writeFile(lessonsPath, `${JSON.stringify([...retainedLessons, ...newLessons], null, 2)}\n`, 'utf8');
await writeFile(dictionaryPath, `${JSON.stringify([...retainedDictionary, ...newDictionary], null, 2)}\n`, 'utf8');
console.log(JSON.stringify({ lessonsAdded: newLessons.length, dictionaryEntriesAdded: newDictionary.length, firstId: maxId + 1, lastId: maxId + rows.length }));
