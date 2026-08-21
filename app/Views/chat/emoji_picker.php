<?php
/**
 * Singleton animated emoji picker — include once on messenger page.
 * Button #prime-chat-emoji lives inside AJAX-loaded conversation.
 */
?>
<div id="prime-chat-emoji-picker" class="pm-emoji-picker hide" aria-hidden="true">
    <div class="pm-emoji-picker-head">
        <input type="search" class="form-control form-control-sm" id="prime-chat-emoji-search" placeholder="Поиск смайликов…" autocomplete="off">
        <button type="button" class="pm-emoji-picker-close" id="prime-chat-emoji-close" title="Закрыть" aria-label="Закрыть">×</button>
    </div>
    <div class="pm-emoji-picker-tabs" id="prime-chat-emoji-tabs"></div>
    <div class="pm-emoji-picker-grid" id="prime-chat-emoji-grid">
        <div class="pm-emoji-picker-loading">Загрузка анимированных смайлов…</div>
    </div>
</div>

<script>
(function initPrimeEmojiPickerSingleton() {
    if (window.__primeEmojiPickerReady) {
        return;
    }
    window.__primeEmojiPickerReady = true;

    var $picker = $('#prime-chat-emoji-picker');
    var $grid = $('#prime-chat-emoji-grid');
    var $tabs = $('#prime-chat-emoji-tabs');
    var $search = $('#prime-chat-emoji-search');
    var emojiCatalog = [];
    var emojiGroups = {};
    var activeGroup = 'all';
    var loading = null;
    var catalogReady = false;
    var ignoreOutsideClick = false;
    var openedAt = 0;

    if (!$picker.length) {
        return;
    }
    // Keep a single picker on <body>
    $picker.appendTo(document.body);

    var FALLBACK = [
        { cp: '1f600', name: 'grinning', group: 'Smileys' },
        { cp: '1f603', name: 'smiley', group: 'Smileys' },
        { cp: '1f604', name: 'smile', group: 'Smileys' },
        { cp: '1f601', name: 'grin', group: 'Smileys' },
        { cp: '1f606', name: 'laughing', group: 'Smileys' },
        { cp: '1f605', name: 'sweat_smile', group: 'Smileys' },
        { cp: '1f602', name: 'joy', group: 'Smileys' },
        { cp: '1f923', name: 'rofl', group: 'Smileys' },
        { cp: '1f62d', name: 'sob', group: 'Smileys' },
        { cp: '1f609', name: 'wink', group: 'Smileys' },
        { cp: '1f618', name: 'kissing_heart', group: 'Smileys' },
        { cp: '1f970', name: 'smiling_face_with_hearts', group: 'Smileys' },
        { cp: '1f60d', name: 'heart_eyes', group: 'Smileys' },
        { cp: '1f929', name: 'star_struck', group: 'Smileys' },
        { cp: '1f973', name: 'partying_face', group: 'Smileys' },
        { cp: '1f525', name: 'fire', group: 'Objects' },
        { cp: '1f4af', name: '100', group: 'Symbols' },
        { cp: '1f389', name: 'tada', group: 'Activities' },
        { cp: '1f44d', name: 'thumbsup', group: 'People' },
        { cp: '1f44f', name: 'clap', group: 'People' },
        { cp: '1f64f', name: 'pray', group: 'People' },
        { cp: '2764', name: 'heart', group: 'Symbols' },
        { cp: '1f495', name: 'two_hearts', group: 'Symbols' },
        { cp: '1f480', name: 'skull', group: 'Smileys' },
        { cp: '1f921', name: 'clown', group: 'Smileys' },
        { cp: '1f47b', name: 'ghost', group: 'Smileys' },
        { cp: '1f4a9', name: 'poop', group: 'Smileys' },
        { cp: '1f37a', name: 'beer', group: 'Food' },
        { cp: '1f37b', name: 'beers', group: 'Food' },
        { cp: '2615', name: 'coffee', group: 'Food' },
        { cp: '1f336', name: 'hot_pepper', group: 'Food' },
        { cp: '1f680', name: 'rocket', group: 'Travel' },
        { cp: '1f3c6', name: 'trophy', group: 'Activities' },
        { cp: '2b50', name: 'star', group: 'Symbols' },
        { cp: '1f31f', name: 'glowing_star', group: 'Symbols' },
        { cp: '1f4a5', name: 'boom', group: 'Smileys' },
        { cp: '1f914', name: 'thinking', group: 'Smileys' },
        { cp: '1f644', name: 'rolling_eyes', group: 'Smileys' },
        { cp: '1f927', name: 'sneeze', group: 'Smileys' },
        { cp: '1f634', name: 'sleeping', group: 'Smileys' }
    ];

    var RU_QUERY = {
        // приветствия / жесты
        привет: ['wave', 'waving', 'folded-hands', 'hug', 'smile', 'grin', 'call-me'],
        приветик: ['wave', 'waving', 'smile'],
        првиет: ['wave', 'waving', 'folded-hands', 'hug', 'smile'],
        здарова: ['wave', 'waving', 'smile'],
        здаров: ['wave', 'waving'],
        здравствуй: ['wave', 'waving', 'folded-hands'],
        здравствуйте: ['wave', 'waving', 'folded-hands'],
        хай: ['wave', 'waving', 'smile'],
        хеллоу: ['wave', 'waving'],
        hello: ['wave', 'waving', 'smile'],
        hi: ['wave', 'waving', 'smile'],
        пока: ['wave', 'waving', 'folded-hands', 'kiss'],
        досвидания: ['wave', 'waving', 'folded-hands'],
        увидимся: ['wave', 'waving', 'wink'],
        помахай: ['wave', 'waving'],
        машет: ['wave', 'waving'],
        рука: ['hand', 'wave', 'ok', 'thumbs', 'clap', 'point'],
        ладонь: ['hand', 'wave', 'raised'],
        жесты: ['hand', 'wave', 'ok', 'thumbs', 'peace', 'clap'],
        ок: ['ok', 'thumbs-up', 'check'],
        окей: ['ok', 'thumbs-up'],
        хорошо: ['ok', 'thumbs-up', 'smile'],
        отлично: ['thumbs-up', 'clap', 'tada', 'fire', '100'],
        супер: ['thumbs-up', 'fire', 'star', '100'],
        круто: ['thumbs-up', 'fire', 'cool', 'sunglasses'],
        лайк: ['thumbs-up', 'thumbs', '+1'],
        дизлайк: ['thumbs-down'],
        класс: ['thumbs-up', 'clap', 'fire', '100'],
        аплодисменты: ['clap'],
        хлопает: ['clap'],
        браво: ['clap', 'tada'],
        мир: ['peace', 'victory'],
        победа: ['victory', 'trophy', 'tada'],
        сила: ['muscle'],
        мускул: ['muscle'],
        обнимашки: ['hug', 'hugging'],
        обнимаю: ['hug', 'hugging', 'heart'],
        обнять: ['hug', 'hugging'],
        обнимает: ['hug'],
        пожалуйста: ['folded-hands', 'pray', 'please'],
        молитва: ['pray', 'folded-hands'],
        спасибо: ['folded-hands', 'pray', 'bow', 'heart', 'kiss'],
        спс: ['folded-hands', 'pray', 'thumbs-up'],
        благодарю: ['folded-hands', 'pray', 'bow'],
        прошу: ['folded-hands', 'pray'],
        извини: ['bow', 'folded-hands', 'sweat'],
        извините: ['bow', 'folded-hands'],
        прости: ['bow', 'folded-hands'],

        // эмоции
        улыбка: ['smile', 'grin', 'smiley', 'warm-smile'],
        улыбается: ['smile', 'grin'],
        смайл: ['smile', 'grin', 'smiley'],
        радость: ['joy', 'smile', 'grin', 'tada', 'partying'],
        счастлив: ['smile', 'grin', 'heart', 'star-struck'],
        счастье: ['smile', 'grin', 'heart'],
        смех: ['joy', 'laugh', 'rofl', 'grin'],
        смеется: ['joy', 'laugh', 'rofl'],
        ржака: ['rofl', 'joy', 'laugh'],
        лол: ['joy', 'laugh', 'rofl'],
        кек: ['joy', 'laugh', 'smirk'],
        подмигивает: ['wink'],
        подмигивание: ['wink'],
        плач: ['sob', 'cry', 'loudly-crying', 'tear'],
        плачет: ['sob', 'cry'],
        слезы: ['sob', 'cry', 'tear'],
        грусть: ['sad', 'cry', 'pensive', 'disappointed'],
        грустно: ['sad', 'cry', 'pensive'],
        злость: ['angry', 'pouting', 'rage'],
        злой: ['angry', 'rage'],
        бесит: ['angry', 'rage'],
        шок: ['scream', 'astonished', 'flushed', 'scared'],
        удивление: ['astonished', 'flushed'],
        страх: ['scared', 'fearful', 'scream'],
        думает: ['thinking'],
        мысль: ['thinking'],
        хмм: ['thinking'],
        сомнение: ['thinking'],
        сон: ['sleep', 'sleepy', 'zzz'],
        спит: ['sleep', 'zzz'],
        устал: ['weary', 'tired', 'sleep', 'yawning'],
        зевает: ['yawning', 'sleep'],
        болезнь: ['sick', 'mask', 'thermometer'],
        болею: ['sick', 'mask', 'thermometer'],
        чихает: ['sneeze'],
        любовь: ['heart', 'kiss', 'love', 'heart-eyes'],
        люблю: ['heart', 'kiss', 'love', 'heart-eyes'],
        влюблен: ['heart', 'heart-eyes', 'kiss'],
        влюблена: ['heart', 'heart-eyes', 'kiss'],
        поцелуй: ['kiss', 'kissing'],
        целую: ['kiss', 'kissing', 'heart'],
        сердечко: ['heart'],
        сердце: ['heart'],
        разбитое: ['broken-heart'],
        огонь: ['fire'],
        огонька: ['fire'],
        горит: ['fire'],
        звезда: ['star', 'glowing-star', 'star-struck'],
        звезды: ['star', 'glowing-star'],
        тусовка: ['party', 'partying', 'tada'],
        праздник: ['tada', 'party', 'confetti', 'balloon'],
        ура: ['tada', 'partying', 'clap'],
        поздравляю: ['tada', 'party', 'clap', 'gift'],
        др: ['cake', 'tada', 'balloon', 'gift'],
        торт: ['cake', 'birthday'],
        клоун: ['clown'],
        призрак: ['ghost'],
        череп: ['skull'],
        какашка: ['poop'],
        кака: ['poop'],
        рофл: ['rofl', 'joy'],

        // животные
        кот: ['cat'], кошка: ['cat'], котик: ['cat'], котенок: ['cat'], котёнок: ['cat'],
        собака: ['dog'], пес: ['dog'], пёс: ['dog'], щенок: ['dog'], собачка: ['dog'],
        мышь: ['mouse'], медведь: ['bear'], мишка: ['bear'], лиса: ['fox'],
        свинья: ['pig'], корова: ['cow'], обезьяна: ['monkey'], птица: ['bird'],
        рыба: ['fish'], тигр: ['tiger'], лев: ['lion'], волк: ['wolf'],
        кролик: ['rabbit'], заяц: ['rabbit'], лягушка: ['frog'], змея: ['snake'],
        лошадь: ['horse', 'racehorse'], единорог: ['unicorn'], дракон: ['dragon'],
        панда: ['panda'], пингвин: ['penguin'], сова: ['owl'], бабочка: ['butterfly'],
        пчела: ['bee'], паук: ['spider'],

        // еда
        еда: ['food', 'hamburger', 'pizza'],
        пиво: ['beer'], кофе: ['coffee'], чай: ['tea'], вино: ['wine'],
        пицца: ['pizza'], бургер: ['hamburger'], суши: ['sushi'],
        яблоко: ['apple'], банан: ['banana'], арбуз: ['watermelon'],
        шоколад: ['chocolate'], конфеты: ['candy'], мороженое: ['ice-cream'],

        // природа
        солнце: ['sun'], луна: ['moon'], дождь: ['rain', 'cloud', 'umbrella'],
        снег: ['snow', 'snowflake'], туча: ['cloud'], радуга: ['rainbow'],
        цветок: ['rose', 'tulip', 'blossom', 'flower', 'bouquet'], роза: ['rose'],
        дерево: ['tree'], море: ['ocean'],

        // вещи / транспорт
        машина: ['car'], авто: ['car'], такси: ['taxi'],
        самолет: ['airplane', 'plane'], самолёт: ['airplane', 'plane'],
        поезд: ['train'], велосипед: ['bicycle'], корабль: ['ship'],
        ракета: ['rocket'], дом: ['house', 'home'], работа: ['briefcase'],
        деньги: ['money'], бабло: ['money'], подарок: ['gift'],
        телефон: ['phone'], письмо: ['mail', 'letter', 'envelope'],
        музыка: ['music', 'note', 'musical'], спорт: ['sport', 'ball', 'soccer', 'trophy'],
        футбол: ['soccer'], трофей: ['trophy'], кубок: ['trophy'], медаль: ['medal'],
        книга: ['book'], комп: ['computer', 'laptop'], ноут: ['laptop'],
        ключ: ['key'], замок: ['lock'], часы: ['watch', 'clock'],

        // люди / статусы
        человек: ['person', 'people', 'man', 'woman'], люди: ['people'],
        мужчина: ['man'], женщина: ['woman'], ребенок: ['baby', 'child'],
        ребёнок: ['baby', 'child'], семья: ['family'],
        флаг: ['flag'], галочка: ['check'], крестик: ['cross', 'x'],
        внимание: ['warning', 'exclamation'], вопрос: ['question'],
        да: ['check', 'thumbs-up', 'ok'], нет: ['cross', 'thumbs-down'],
        стоп: ['stop', 'hand'], го: ['rocket', 'check'], поехали: ['rocket', 'car'],
        срочно: ['exclamation', 'fire'], важно: ['exclamation', 'star'],
        готово: ['check', 'tada', 'ok'], жду: ['hourglass', 'watch', 'thinking'],
        дома: ['house', 'home'], офис: ['office'], отпуск: ['beach', 'airplane', 'sun']
    };

    var EN_RU = {
        wave: 'привет пока машет помахай хай здарова',
        waving: 'привет пока машет',
        hug: 'обнимашки обнимаю привет',
        hugging: 'обнимашки обнимаю',
        'folded-hands': 'спасибо пожалуйста молитва привет прошу',
        pray: 'спасибо молитва пожалуйста',
        please: 'пожалуйста',
        'thumbs-up': 'лайк класс ок хорошо супер отлично да',
        'thumbs-down': 'дизлайк нет плохо',
        ok: 'ок окей хорошо',
        clap: 'аплодисменты браво хлопает',
        peace: 'мир победа',
        victory: 'победа',
        muscle: 'сила мускул',
        cat: 'кот кошка котик', dog: 'собака пёс пес щенок', bear: 'медведь мишка',
        fox: 'лиса', pig: 'свинья', cow: 'корова', monkey: 'обезьяна', bird: 'птица',
        fish: 'рыба', tiger: 'тигр', lion: 'лев', wolf: 'волк', rabbit: 'кролик заяц',
        frog: 'лягушка', snake: 'змея', horse: 'лошадь', racehorse: 'лошадь',
        unicorn: 'единорог', dragon: 'дракон', panda: 'панда', penguin: 'пингвин',
        heart: 'сердце любовь люблю сердечко привет спасибо',
        'broken-heart': 'разбитое сердце грусть',
        fire: 'огонь круто супер класс',
        star: 'звезда', joy: 'смех радость лол', laugh: 'смех лол',
        smile: 'улыбка смайл привет радость', grin: 'улыбка',
        wink: 'подмигивание', sob: 'плач слезы', cry: 'плач грусть',
        angry: 'злость злой бесит', thinking: 'думает мысль хмм',
        sleep: 'сон спит', kiss: 'поцелуй целую любовь',
        beer: 'пиво', coffee: 'кофе', tea: 'чай', pizza: 'пицца', cake: 'торт др',
        rocket: 'ракета поехали го', trophy: 'трофей кубок', ghost: 'призрак',
        skull: 'череп', poop: 'какашка кака', clown: 'клоун',
        tada: 'праздник ура поздравляю', party: 'праздник тусовка',
        rose: 'роза цветок', sun: 'солнце', moon: 'луна', snowflake: 'снег',
        car: 'машина авто', airplane: 'самолет самолёт', money: 'деньги бабло',
        gift: 'подарок', phone: 'телефон', music: 'музыка', check: 'галочка да готово',
        house: 'дом дома', home: 'дом дома'
    };

    function cpToEmoji(cp) {
        try {
            return String.fromCodePoint.apply(null, String(cp).split('_').map(function (h) {
                return parseInt(h, 16);
            }).filter(function (n) { return !isNaN(n); }));
        } catch (e) {
            return '';
        }
    }

    function webpUrl(cp) {
        return 'https://fonts.gstatic.com/s/e/notoemoji/latest/' + cp + '/512.webp';
    }

    function groupLabel(g) {
        var map = {
            Smileys: 'Смайлы', People: 'Люди', Animals: 'Животные', Food: 'Еда',
            Travel: 'Путешествия', Activities: 'Активности', Objects: 'Объекты',
            Symbols: 'Символы', Flags: 'Флаги', all: 'Все'
        };
        return map[g] || g;
    }

    function uniq(arr) {
        var seen = {};
        var out = [];
        arr.forEach(function (x) {
            x = String(x || '').toLowerCase();
            if (!x || seen[x]) return;
            seen[x] = 1;
            out.push(x);
        });
        return out;
    }

    function ruForTags(tags) {
        var parts = [];
        (tags || []).forEach(function (tag) {
            var clean = String(tag || '').toLowerCase().replace(/^:|:$/g, '').replace(/-/g, ' ');
            clean.split(/\s+/).forEach(function (tok) {
                if (EN_RU[tok]) parts.push(EN_RU[tok]);
                Object.keys(EN_RU).forEach(function (k) {
                    if (tok.indexOf(k) !== -1) parts.push(EN_RU[k]);
                });
            });
        });
        return parts.join(' ');
    }

    function expandSearchQuery(q) {
        q = $.trim(String(q || '').toLowerCase().replace(/\s+/g, ' '));
        if (!q) return [];
        var terms = [q];
        Object.keys(RU_QUERY).forEach(function (ru) {
            if (ru === q || ru.indexOf(q) === 0 || q.indexOf(ru) === 0 || (q.length >= 3 && ru.indexOf(q) !== -1)) {
                terms.push(ru);
                terms = terms.concat(RU_QUERY[ru]);
            }
        });
        if (/^[a-z0-9+\- ]+$/i.test(q)) {
            terms.push(q.replace(/\s+/g, '-'));
        }
        return uniq(terms);
    }

    function buildSearchBlob(item) {
        return uniq([item.name, item.cp, (item.tags || []).join(' '), item.ru || ''])
            .join(' ').toLowerCase().replace(/:/g, ' ').replace(/-/g, ' ');
    }

    function itemMatchesQuery(item, terms) {
        if (!terms.length) return true;
        var hay = item.search || buildSearchBlob(item);
        for (var i = 0; i < terms.length; i++) {
            if (hay.indexOf(terms[i]) !== -1) return true;
        }
        return false;
    }

    function enrichItem(item) {
        item.tags = item.tags || [item.name];
        item.ru = ruForTags(item.tags);
        item.search = buildSearchBlob(item);
        return item;
    }

    function setCatalog(list) {
        emojiCatalog = (list || []).map(enrichItem);
        emojiGroups = { all: emojiCatalog };
        emojiCatalog.forEach(function (item) {
            var g = item.group || 'Smileys';
            if (!emojiGroups[g]) emojiGroups[g] = [];
            emojiGroups[g].push(item);
        });
        renderTabs();
        renderGrid();
    }

    function renderTabs() {
        var order = ['all', 'Smileys', 'People', 'Animals', 'Food', 'Travel', 'Activities', 'Objects', 'Symbols'];
        var html = '';
        order.forEach(function (g) {
            if (!emojiGroups[g] || !emojiGroups[g].length) return;
            html += '<button type="button" class="pm-emoji-tab' + (g === activeGroup ? ' is-active' : '') + '" data-group="' + g + '">' + groupLabel(g) + '</button>';
        });
        $tabs.html(html);
    }

    function syncActiveTab() {
        $tabs.find('.pm-emoji-tab').each(function () {
            $(this).toggleClass('is-active', $(this).attr('data-group') === activeGroup);
        });
    }

    function renderGrid() {
        var q = $.trim($search.val() || '');
        var terms = expandSearchQuery(q);
        var list = terms.length ? emojiCatalog : (emojiGroups[activeGroup] || emojiCatalog);
        var html = '';
        list.forEach(function (item) {
            if (!itemMatchesQuery(item, terms)) return;
            var emoji = cpToEmoji(item.cp);
            if (!emoji) return;
            var title = (item.ru ? item.ru.split(' ')[0] + ' · ' : '') + (item.name || '');
            html += '<button type="button" class="pm-emoji-cell js-pm-insert-emoji" data-emoji="' + emoji + '" title="' + title.replace(/"/g, '&quot;') + '">'
                + '<img src="' + webpUrl(item.cp) + '" alt="' + emoji + '" loading="lazy" decoding="async" draggable="false" onerror="this.style.display=\'none\';this.parentNode.appendChild(document.createTextNode(this.alt));">'
                + '</button>';
        });
        $grid.html(html || '<div class="pm-emoji-picker-loading">Ничего не найдено</div>');
    }

    function loadCatalog() {
        if (catalogReady) return $.Deferred().resolve().promise();
        if (loading) return loading;
        loading = $.ajax({
            url: 'https://googlefonts.github.io/noto-emoji-animation/data/api.json',
            dataType: 'json',
            timeout: 8000
        }).then(function (data) {
            var list = [];
            var icons = (data && data.icons) ? data.icons : [];
            icons.forEach(function (icon) {
                var cp = String(icon.codepoint || '').toLowerCase();
                if (!cp) return;
                var tags = (icon.tags || []).map(function (t) { return String(t).replace(/^:|:$/g, ''); });
                var tag = tags[0] || cp;
                var group = 'Smileys';
                var cats = icon.categories || icon.category || [];
                if (typeof cats === 'string') cats = [cats];
                if (cats && cats[0]) {
                    group = String(cats[0]).split(/[\/&]/)[0].trim() || group;
                    if (/smiley|emotion/i.test(group)) group = 'Smileys';
                    else if (/people|body|hand/i.test(group)) group = 'People';
                    else if (/animal|nature/i.test(group)) group = 'Animals';
                    else if (/food|drink/i.test(group)) group = 'Food';
                    else if (/travel|place/i.test(group)) group = 'Travel';
                    else if (/activit|event|sport/i.test(group)) group = 'Activities';
                    else if (/object/i.test(group)) group = 'Objects';
                    else if (/symbol/i.test(group)) group = 'Symbols';
                    else if (/flag/i.test(group)) group = 'Flags';
                    else group = 'Smileys';
                }
                list.push({ cp: cp, name: tag, tags: tags, group: group });
            });
            if (!list.length) {
                list = FALLBACK.map(function (x) { return { cp: x.cp, name: x.name, tags: [x.name], group: x.group }; });
            }
            catalogReady = true;
            setCatalog(list);
        }, function () {
            catalogReady = true;
            if (!emojiCatalog.length) {
                setCatalog(FALLBACK.map(function (x) { return { cp: x.cp, name: x.name, tags: [x.name], group: x.group }; }));
            }
        });
        return loading;
    }

    function positionPicker() {
        var btn = document.getElementById('prime-chat-emoji');
        if (!btn) return;
        var rect = btn.getBoundingClientRect();
        var width = Math.min(420, Math.max(280, window.innerWidth - 24));
        var left = Math.round(rect.right - width);
        left = Math.max(12, Math.min(left, window.innerWidth - width - 12));
        var bottom = Math.round(window.innerHeight - rect.top + 10);
        bottom = Math.max(12, bottom);
        $picker.css({
            position: 'fixed',
            left: left + 'px',
            right: 'auto',
            bottom: bottom + 'px',
            top: 'auto',
            width: width + 'px',
            maxWidth: 'calc(100vw - 24px)',
            zIndex: 10050
        });
    }

    function openPicker() {
        if (!emojiCatalog.length) {
            setCatalog(FALLBACK.map(function (x) { return { cp: x.cp, name: x.name, tags: [x.name], group: x.group }; }));
        }
        ignoreOutsideClick = true;
        openedAt = Date.now();
        $picker.removeClass('hide').attr('aria-hidden', 'false');
        $('#prime-chat-emoji').addClass('is-active').attr('aria-expanded', 'true');
        positionPicker();
        loadCatalog().always(function () {
            positionPicker();
        });
        setTimeout(function () { ignoreOutsideClick = false; }, 300);
    }

    function closePicker() {
        $picker.addClass('hide').attr('aria-hidden', 'true');
        $('#prime-chat-emoji').removeClass('is-active').attr('aria-expanded', 'false');
    }

    function insertEmoji(emoji) {
        var $input = $('#prime-chat-input');
        var el = $input.get(0);
        if (!el || !emoji) {
            return;
        }
        var value = $input.val() || '';
        var start = typeof el.selectionStart === 'number' ? el.selectionStart : value.length;
        var end = typeof el.selectionEnd === 'number' ? el.selectionEnd : value.length;
        $input.val(value.substring(0, start) + emoji + value.substring(end));
        var caret = start + emoji.length;
        try {
            el.focus();
            el.setSelectionRange(caret, caret);
        } catch (e) {}
        $input.trigger('input');
    }

    // Delegated open/close — survives AJAX conversation reloads
    $(document).off('click.primeEmojiBtn', '#prime-chat-emoji').on('click.primeEmojiBtn', '#prime-chat-emoji', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        if ($picker.hasClass('hide')) {
            openPicker();
        } else {
            closePicker();
        }
    });

    // Bind ON the picker itself — do NOT stopPropagation on the whole picker,
    // or delegated document clicks for cells/tabs never fire.
    $picker.off('.primeEmoji');

    $picker.on('click.primeEmoji', '#prime-chat-emoji-close', function (e) {
        e.preventDefault();
        e.stopPropagation();
        closePicker();
    });

    $picker.on('click.primeEmoji', '.pm-emoji-tab', function (e) {
        e.preventDefault();
        e.stopPropagation();
        activeGroup = $(this).attr('data-group') || 'all';
        syncActiveTab();
        renderGrid();
    });

    $picker.on('input.primeEmoji', '#prime-chat-emoji-search', function () {
        renderGrid();
    });

    $picker.on('click.primeEmoji', '.js-pm-insert-emoji', function (e) {
        e.preventDefault();
        e.stopPropagation();
        insertEmoji($(this).attr('data-emoji') || '');
        // keep picker open for multi-insert
    });

    $(document).off('click.primeEmojiOutside').on('click.primeEmojiOutside', function (e) {
        if (ignoreOutsideClick || $picker.hasClass('hide')) {
            return;
        }
        if (Date.now() - openedAt < 350) {
            return;
        }
        if ($(e.target).closest('#prime-chat-emoji-picker, #prime-chat-emoji').length) {
            return;
        }
        closePicker();
    });

    $(window).off('resize.primeEmoji scroll.primeEmoji').on('resize.primeEmoji scroll.primeEmoji', function () {
        if (!$picker.hasClass('hide')) {
            positionPicker();
        }
    });

    window.primeCloseEmojiPicker = closePicker;
    window.primeInsertEmoji = insertEmoji;
})();
</script>
