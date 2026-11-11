// Icon groups mapped to @lucide/vue component names
// PascalCase name = kebab-case id → Lucide component name
// Override table for ids that differ from kebab→Pascal conversion

const OVERRIDE = {
  'shopping-bag': 'ShoppingBag',
  'shopping-cart': 'ShoppingCart',
  'credit-card': 'CreditCard',
  'dollar-sign': 'DollarSign',
  'money-bag': 'Banknote',
  'gas-station': 'Fuel',
  'ice-cream': 'IceCream',
  'file-text': 'FileText',
  'chart-bar': 'ChartBar',
  'more-horizontal': 'MoreHorizontal',
  'home-heart': 'Heart',
  'cake-birthday': 'CakeSlice',
  'water-drop': 'Droplet',
  'cash': 'Banknote',
  'bank': 'Building2',
  'chart': 'ChartArea',
  'safe': 'Shield',
  'coin': 'CircleDollarSign',
  'parking': 'Navigation',
  'basket': 'ShoppingBasket',
  'mug': 'CupSoda',
  'couch': 'Sofa',
  'shower': 'ShowerHead',
  'garden': 'Flower2',
  'tool': 'Wrench',
  'doctor': 'Stethoscope',
  'run': 'Footprints',
  'campfire': 'Flame',
  'flask': 'Beaker',
  'refresh': 'RefreshCw',
}

function toLucide(id) {
  if (OVERRIDE[id]) return OVERRIDE[id]
  return id.split('-').map(s => s.charAt(0).toUpperCase() + s.slice(1)).join('')
}

export const iconGroups = [
  {
    id: 'finance', title: 'Финансы',
    icons: ['wallet','cash','credit-card','bank','money-bag','receipt','percent','chart','safe','coin','dollar-sign','banknote','landmark','trending-up','trending-down'].map(id => ({ id, lucide: toLucide(id) })),
  },
  {
    id: 'transport', title: 'Транспорт',
    icons: ['car','bus','train','plane','bike','scooter','ship','truck','gas-station','parking','fuel','navigation','map-pin','compass'].map(id => ({ id, lucide: toLucide(id) })),
  },
  {
    id: 'shopping', title: 'Покупки',
    icons: ['shopping-bag','shopping-cart', 'gift','tag','basket','box','store','dollar-sign','package','baggage-claim','archive','layers'].map(id => ({ id, lucide: toLucide(id) })),
  },
  {
    id: 'food', title: 'Еда и напитки',
    icons: ['shopping-basket', 'ham', 'coffee','utensils','pizza','cake','apple','ice-cream','cup-soda','wine','beer','mug','popcorn','sandwich', 'glass-water'].map(id => ({ id, lucide: toLucide(id) })),
  },
  {
    id: 'home', title: 'Дом',
    icons: ['house', 'washing-machine', 'bed','lamp','couch','shower','key','garden','tool','sofa','armchair','building2','fence'].map(id => ({ id, lucide: toLucide(id) })),
  },
  {
    id: 'health', title: 'Здоровье',
    icons: ['heart','pill','hospital','doctor','thermometer','bandage','scale','activity','plus','ambulance','bone','paw-print'].map(id => ({ id, lucide: toLucide(id) })),
  },
  {
    id: 'beauty', title: 'Красота',
    icons: ['sparkles','scissors','paintbrush','spray-can','droplets','flower2','sun','moon','star','gem','sparkle','palette'].map(id => ({ id, lucide: toLucide(id) })),
  },
  {
    id: 'entertainment', title: 'Развлечение',
    icons: ['gamepad2','music','film','ticket','theater','dices','palette','guitar','headphones','radio','clapperboard','puzzle'].map(id => ({ id, lucide: toLucide(id) })),
  },
  {
    id: 'bills', title: 'Счета',
    icons: ['file-text', 'phone-call', 'calculator','pen','calendar','mail','chart-bar','receipt','file','printer','clipboard','scroll','wallet-cards'].map(id => ({ id, lucide: toLucide(id) })),
  },
  {
    id: 'sport', title: 'Спорт',
    icons: ['dumbbell','trophy','target','run','bike','whistle','shirt','volleyball','swords','medal','flame','zap'].map(id => ({ id, lucide: toLucide(id) })),
  },
  {
    id: 'leisure', title: 'Отдых',
    icons: ['sun','tree-pine','tent','fish','camera','mountain','umbrella','palmtree','campfire','flower','trees','moon'].map(id => ({ id, lucide: toLucide(id) })),
  },
  {
    id: 'education', title: 'Образование',
    icons: ['book','book-open','graduation-cap','pencil','laptop','library','globe','flask','monitor','school','backpack','clipboard-pen'].map(id => ({ id, lucide: toLucide(id) })),
  },
  {
    id: 'family', title: 'Семья/дети',
    icons: ['user-round-group', 'users','baby','dog','cat','heart','gift','cake','house','toy-brick','blocks','rabbit','bird'].map(id => ({ id, lucide: toLucide(id) })),
  },
  {
    id: 'farm', title: 'Ферма',
    icons: ['tractor','egg','droplet','wind','wheat','sprout','shrub','tree-pine','sun','flower','fence','combine'].map(id => ({ id, lucide: toLucide(id) })),
  },
  {
    id: 'other', title: 'Другое',
    icons: ['settings','star','clock','cloud','target','flag','lightbulb','more-horizontal','refresh','search','bell','trash'].map(id => ({ id, lucide: toLucide(id) })),
  },
]

export const ALL_ICONS = new Map(
  iconGroups.flatMap(g => g.icons.map(i => [i.id, i.lucide]))
)