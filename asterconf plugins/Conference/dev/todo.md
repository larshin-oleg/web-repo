# План

* Выгрузка списков потенциальных посетителей с фильтрами [ - ]

# Сделано

* Разделение конференций через направление сделки [ + ]
* Привязка регистраций к конференции [ + ]
* Моментальная оплата ( прием наличных ) [ + ]
* Установка Webhook на генерацию документов через настройки [ + ]
* Обработчик Webhook на генерацию документов [ + ]

# Webhook

У нас интеграция с сайтом автоматизирована:
1. создется клиент, контакт, сделка
2. добавляется спецификация по выбранным позициям при регистрации
3. генерится документ с актом
4. генерится счет
5. Все доки отправляются почтой клиенту
6. ставится напоминалка для манагера

# SQL

> привязываем записи 2018 года к направлению Asterconf 2018
`UPDATE conference SET conf = '2' WHERE DATE_FORMAT(datum, '%Y') = '2018';`

> привязываем записи 2019 года к направлению Asterconf 2019
`UPDATE conference SET conf = '2' WHERE DATE_FORMAT(datum, '%Y') = '2019';`

> привязываем артикулы прайса ко всем позициям спецификации
`UPDATE salesman_speca SET artikul = (SELECT artikul FROM salesman_price WHERE salesman_price.n_id = salesman_speca.prid) WHERE salesman_speca.prid > 0;`

> привязываем сделки к правильному направлению
`UPDATE salesman_dogovor SET direction = '2' WHERE DATE_FORMAT(datum, '%Y') = '2018';
 UPDATE salesman_dogovor SET direction = '3' WHERE DATE_FORMAT(datum, '%Y') = '2019';`

# Подключение плагина

В файле /plugins/map.castom.json добавить:

`
"conference": {
	"name": "Conference",
	"url": "plugins/Conference/",
	"content": "Плагин для конференций",
	"icon": "icon-asterisk",
	"interface": "yes",
	"js": "/plugins/Conference/js/conference.js"
},
`

# Фильтры для выгрузок

Asterconf и Asterconf – online = Типы сделок
Сокращения (У, Б, Г, С + ...) = Артикулы

1. День 1 (оплачено)
Asterconf && ((Оплата && ( У1 || У3) ) || У1Б || У3Б)

2. День 2 (оплачено)
Asterconf && ((Оплата && (У2 || У3)) || У2Б || У3Б)

3. Оба дня (оплачено)
Asterconf && ((У3 && Оплата)  || У3Б)

4. Онлайн (оплачено)
Asterconf-online && ((УО && Оплата) || УОБ)

5. Обеды день 1 (оплачено)
Asterconf && ((Оплата && (БЛ1 || БЛ3)) || БЛ1Б || БЛ3Б)

6. Обеды день 2 (оплачено)
Asterconf && ((Оплата && (БЛ1 || БЛ3)) || БЛ1Б || БЛ3Б)

7. Банкет (оплачено)
Asterconf && ((Оплата && Б2) || Б2Б)

8. Гостиница (оплачено)
Asterconf && Оплата && (Г1 || Г2)

9. День 1 (забронированно)
Asterconf && (!Оплата && (У1 || У3))

10. День 2 (забронированно)
Asterconf && (!Оплата && (У2 || У3))

11. Оба дня (забронированно)
Asterconf  = Asterconf 2019
Оплачено = забронированно

12. Онлайн (забронированно)
Asterconf-online && УО && !Оплата

13. Обеды день 1 (забронированно)
Asterconf && !Оплата && (БЛ1 || БЛ3)

14. Обеды день 2 (забронированно)
Asterconf && !Оплата && (БЛ2 || БЛ3)

15. Банкет (забронированно)
Asterconf && !Оплата && Б2

16. Гостиница (забронированно)
Asterconf && !Оплата && (Г1 || Г2)

17. Все контакты (2019)
Направление Asterconf 2019 && Asterconf

18. Оплачено
Направление Asterconf 2019 && Оплата