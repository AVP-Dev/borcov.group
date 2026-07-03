<?php

declare(strict_types=1);

return [
    'categories' => [
        'website_builder' => [
            'label' => 'Website Builder',
            'patterns' => [
                'site builder', 'website builder', 'create site', 'create website',
                'build site', 'build website', 'make site', 'make website',
                'landing page', 'landing', 'website creator', 'site creator',
                'web builder', 'page builder', 'drag and drop', 'wysiwyg',
                'website design', 'web design', 'site design',
                'blog', 'portfolio site', 'business site',
                'online store', 'ecommerce', 'e-commerce', 'shop',
                'website template', 'site template', 'web template',
            ],
            'patterns_ru' => [
                'сайт', 'лендинг', 'посадочная страница', 'конструктор сайтов',
                'создать сайт', 'сделать сайт', 'разработать сайт',
                'веб-дизайн', 'дизайн сайта', 'веб-сайт',
                'интернет-магазин', 'онлайн магазин', 'интернет магазин',
                'блог', 'портфолио', 'сайт визитка',
                'шаблон сайта', 'лендинг пейдж',
                'конструктор', 'бесплатный конструктор',
                'как сделать сайт', 'как создать сайт', 'как сделать интернет магазин',
            ],
        ],
        'email' => [
            'label' => 'Email',
            'patterns' => [
                'email', 'e-mail', 'mail', 'webmail', 'email hosting',
                'business email', 'professional email', 'email account',
                'email service', 'email provider', 'mail server',
                'smtp', 'imap', 'pop3', 'email forwarding',
                'email for domain', 'custom email', 'email solution',
            ],
            'patterns_ru' => [
                'почта', 'эл. почта', 'электронная почта', 'email',
                'корпоративная почта', 'бизнес почта', 'почтовый ящик',
                'почтовый сервер', 'почтовый сервис', 'почта для домена',
                'e-mail', 'имейл', 'email хостинг',
            ],
        ],
        'domains' => [
            'label' => 'Domains',
            'patterns' => [
                'domain', 'domain name', 'register domain', 'buy domain',
                'domain registration', 'domain search', 'domain check',
                'domain transfer', 'domain renewal', 'domain pricing',
                'domain provider', 'domain registrar', 'domain extension',
                'tld', 'subdomain', 'dns', 'whois',
                'cheap domain', 'free domain',
            ],
            'patterns_ru' => [
                'домен', 'доменное имя', 'регистрация домена', 'купить домен',
                'проверка домена', 'трансфер домена', 'продление домена',
                'домен в подарок', 'домен бесплатно', 'дешёвый домен',
                'dns', 'зона', 'поддомен', 'whois',
            ],
        ],
        'accounting' => [
            'label' => 'Accounting',
            'patterns' => [
                'accounting', 'accounting software', 'accounting tool',
                'bookkeeping', 'bookkeeping software', 'erp',
                'financial software', 'accounting system',
                'business accounting', 'online accounting',
                'tax', 'tax software', 'tax preparation',
                'expense tracking', 'expense management',
                'financial management', 'accounting platform',
            ],
            'patterns_ru' => [
                'бухгалтерия', 'бухучёт', 'бухгалтерский учёт', 'бухгалтерия онлайн',
                'бухгалтерское обслуживание', 'бухгалтерская программа',
                'erp', 'учёт', 'финансовый учёт',
                'налоги', 'налоговый учёт', 'отчётность',
                'управление расходами', 'финансы',
            ],
        ],
        'invoicing' => [
            'label' => 'Invoicing',
            'patterns' => [
                'invoice', 'invoicing', 'invoice software', 'invoice generator',
                'invoice maker', 'invoice template', 'create invoice',
                'send invoice', 'billing', 'billing software',
                'online invoicing', 'free invoice', 'invoice system',
                'payment', 'payment processing', 'payment gateway',
                'receipt', 'receipt generator', 'estimate',
            ],
            'patterns_ru' => [
                'счет', 'выставление счета', 'счёт-фактура', 'выставить счёт',
                'биллинг', 'биллинговая система', 'онлайн счета',
                'оплата', 'приём платежей', 'платёжный шлюз',
                'квитанция', 'счет на оплату', 'счётчик',
            ],
        ],
        'general_brand' => [
            'label' => 'General Brand',
            'patterns' => [
                'site.pro', 'site pro', 'sitepro',
            ],
            'patterns_ru' => [
                'site.pro', 'site pro', 'sitepro',
            ],
        ],
        'reseller' => [
            'label' => 'Reseller',
            'patterns' => [
                'reseller', 'reseller hosting', 'reseller program',
                'white label', 'whitelabel', 'private label',
                'hosting provider', 'web hosting reseller',
                'partner program', 'become a partner', 'affiliate program',
                'agency', 'agency platform', 'agency partner',
                'multi-tenant', 'multitenant', 'client management',
            ],
            'patterns_ru' => [
                'перепродажа', 'реселлер', 'реселлерский',
                'white label', 'whitelabel', 'частная марка',
                'партнёрская программа', 'партнёр', 'агент',
                'провайдер хостинга', 'хостинг провайдер',
                'агентство', 'агенция',
            ],
        ],
    ],
    'audience_segments' => [
        'b2b' => [
            'patterns' => [
                'reseller', 'white label', 'whitelabel', 'private label',
                'partner', 'partner program', 'business partner',
                'hosting provider', 'web host', 'hosting company',
                'agency', 'agency platform', 'digital agency',
                'enterprise', 'enterprise solution', 'business solution',
                'wholesale', 'for teams', 'team plan',
                'for hosting', 'for providers', 'for agencies',
                'multi-user', 'multi user',
            ],
            'patterns_ru' => [
                'реселлер', 'перепродажа', 'партнёр', 'партнёрская программа',
                'хостинг провайдер', 'провайдер хостинга',
                'агентство', 'агенция', 'для агентств',
                'предприятие', 'для бизнеса', 'b2b',
                'оптом', 'white label', 'частная марка',
                'корпоративный', 'юридическое лицо', 'организация',
            ],
        ],
    ],
    'intents' => [
        'commercial' => [
            'patterns' => [
                'buy', 'purchase', 'order', 'get',
                'price', 'prices', 'pricing', 'cost', 'costs',
                'cheap', 'cheapest', 'affordable', 'best',
                'deal', 'deals', 'offer', 'offers', 'promo',
                'discount', 'coupon', 'voucher', 'sale',
                'compare', 'comparison', 'vs', 'versus',
                'top', 'best', 'review', 'reviews', 'rating',
            ],
            'patterns_ru' => [
                'купить', 'заказать', 'приобрести', 'цена', 'цены',
                'стоимость', 'дешёвый', 'дешево', 'недорого',
                'скидка', 'скидки', 'акция', 'предложение',
                'промокод', 'купон', 'промо',
                'лучший', 'лучшие', 'рейтинг', 'топ',
                'сравнить', 'сравнение',
                'отзыв', 'отзывы',
            ],
        ],
        'informational' => [
            'patterns' => [
                'how to', 'how do i', 'how can i', 'how is', 'how are',
                'what is', 'what are', 'what does', 'what do',
                'why', 'when', 'where', 'which',
                'guide', 'tutorial', 'lesson', 'course',
                'learn', 'training', 'education',
                'tips', 'tricks', 'help', 'support',
                'example', 'examples', 'sample', 'samples',
                'free', 'try', 'demo', 'test',
                'definition', 'meaning', 'explain',
                'install', 'setup', 'configure', 'integration',
            ],
            'patterns_ru' => [
                'как', 'как сделать', 'как создать', 'как настроить',
                'как установить', 'как подключить', 'как работать',
                'что такое', 'что значит', 'что это',
                'почему', 'зачем', 'когда', 'где',
                'инструкция', 'руководство', 'гайд',
                'урок', 'обучение', 'курс', 'туториал',
                'пример', 'примеры', 'шаблон',
                'бесплатно', 'бесплатный', 'бесплатная', 'бесплатные',
                'попробовать', 'демо', 'тест',
                'советы', 'рекомендации', 'помощь',
                'установка', 'настройка', 'интеграция',
            ],
        ],
        'navigational' => [
            'patterns' => [
                'site.pro', 'site pro', 'sitepro',
            ],
            'patterns_ru' => [
                'site.pro', 'site pro', 'sitepro', 'сайт про',
            ],
        ],
    ],
];
