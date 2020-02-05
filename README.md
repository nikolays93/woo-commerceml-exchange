### Автоматизация обмена данными товаров и товарных предложений между 1C и Wordpress

Этот плагин позволяет автоматизировать наполнение товарами сайта на Wordpress с установленным плагином Woocommerce.

### Особенности плагина
* Создает дополнительную таксономию склад
* Добавляет произвольные поля: Ед. изм, внешний код, налоговая ставка
* Внедряет дополнительные поля в интерфейс изменения товара woocommerce
* Возможность создавать новые товары и обновлять существующие, так же __только создавать__ или __только обновлять__
* Возможность выбора что обновлять у уже существующих товаров
* Возможность деактивировать, устанавливать статус "нет в наличии", удалять товары после полной выгрузки, а так же если у товара не указана цена или совсем нет товарного предложения.

### На данный момент не работает
* Нет поддержки вариативных товаров

### В ближайшее время планируется
* Дописать особенности этого плагина
* Не работает пометка удаления в новых версиях 1C УТ

### Протокол обмена данными с сайтом commerceML

[Документация по протоколу обмена](http://v8.1c.ru/edi/edi_stnd/131/)

##### 1. Начало сеанса (Авторизация)
Выгрузка данных начинается с того, что система "1С:Предприятие" отправляет http-запрос следующего вида:
_?type=catalog&mode=checkauth_  

##### 2. Уточнение параметров сеанса
Система запрашивает возможности сервера  
_?type=catalog&mode=init_
```
zip=yes|no - Сервер поддерживает Zip
file_limit=<число> - максимально допустимый размер файла в байтах для передачи за один запрос
```
...

_Developed by me with support of the company: [SEO18](//seo18.ru)_
 
