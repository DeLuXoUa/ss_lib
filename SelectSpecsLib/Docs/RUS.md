#Создание объекта (инициализация коннекта)

$ssapi = new SSAPI($host, $port, $auth_token, $auth_group, $timeout = 15, $notify_mode = true, $mode = SSAPI_CONNECTION_NOTIFYS_ENABLE | SSAPI_CONNECTION_ENCRIPTION_DISABLE)

$host - урл сервера

$port - порт сервера

$auth_key_id - номер секретного ключа

$auth_token - секретный ключ

$auth_group_id - айди группы по умолчанию

$custom_client_id - уникальный ключ клиента, который задаёт сам клиент

$timeout = 15 - время ожидания ответа (п умолчанию 15 секунд)

$mode - режим подключения задаёться флагами
* SSAPI_CONNECTION_NOTIFYS_ENABLE - включает режим уведомления (активно по умолчанию), создаёт второй коннект не требующий ответа
* SSAPI_CONNECTION_NOTIFYS_DISABLE - выключает режим уведомлений
* SSAPI_CONNECTION_ENCRIPTION_ENABLE - включает шифровку передаваемых данных (в версии 0.5 не аткивно)
* SSAPI_CONNECTION_ENCRIPTION_DISABLE - выключает шифровку данных


## Работа с апи:
Работа достаточно проста, и заключается в однотипном вызове функций, где можно опустить любой параметр (или пропустить указав его как NULL).
Все функции всегда возвращают array однотипного формата:

При ошибке: **['result' => 'false', 'reason'=>'error message']**

При успехе: **['result' => 'true', 'result'=>[...]]**


### SSAPI::order($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет ордер в апи.

### SSAPI::order_items($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет айтем в ордере в апи.

### SSAPI::items($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет айтем в апи.

### SSAPI::item_images($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет картинки в айтемах в апи.

### SSAPI::item_translations($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет перевод для айтема в апи.

### SSAPI::item_categories($search, $data, $flags, $options)
Заменяет(обновляет) иликатегорию для айтема в апи.

### SSAPI::groups($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет группы в апи.

### SSAPI::group_rules($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет правилла группы в апи.

### SSAPI::group_domains($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет домены группы в апи.

### SSAPI::users($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет юзеров в апи.

### SSAPI::user_profiles($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет профили юзеров в апи.

###OMNIS аналоги
все функции для омниса аналогичны обычным, но добавляеться префикс "omnis_"
* omnis_orders
* omnis_order_items
* omnis_items
* omnis_item_images
* omnis_item_translations
* omnis_item_categories
* omnis_users
* omnis_user_profiles
* omnis_groups
* omnis_group_rules
* omnis_group_domains

Параметры:

**$search** - паремтры поиска записи в апи, например: `['id' => '1234', 'order_id' => '3342']`

**$data** - обект для записи в апи, например: `['id' => '1234', 'order_id' => '3342', 'items' => [...]]`

**$flags** - флаги для выбора сценария:

* SSAPI_RETURN_RESULT - возвращать результат выполнения (объекты записи)
* SSAPI_NO_WAIT_RESPONSE - не ждать ответа от сервера, и продолжить работу
* SSAPI_FULL_REWRITE - удалить старые значения удовлетворяющие критериям и записать данные
* SSAPI_CREATE_IF_NOT_EXIST - создать если нет искомого объекта (аналог REPLACE)
* SSAPI_DELETE_DOCUMENT - удалить документ
* SSAPI_ONLY_IN_GROUP - поиск только по группе
* SSAPI_MULTI_QUERY - массив запросов одновременно
* SSAPI_TASK_FOR_JOB_SERVER - отправить задачу в очередь

**$options** - массив дополнительных опций (сортировка, требуемые поля и т.д.)

`["limit" => 20]` - количество значений к возврату (максимум 1000)

`["skip" => 20]` - пропустить первые Х значений к возврату

`["fields" => ["fieldname", "fieldname", "fieldname"]]` - отображать указанные поля

`["fields" => [["hide" => ["fieldname", "fieldname", "fieldname"]]]` - отображать указанные поля

`["order" => ["fieldname"=>"ASC"|"DESC"]]` - сортировка по одному полю

`["order" => [["fieldname"=>"ASC"|"DESC"],["fieldname"=>"ASC"|"DESC"],["fieldname"=>"ASC"|"DESC"]]]` - сортировка по нескольким полям


### пример:

    require('lib/NodeAPI.php');

//  NodeAPI($host, $port, $auth_key_id, $auth_token, $auth_group_id, $custom_client_id, $timeout = 15, $mode = NULL)
//два последних параметра не обязательные
//если не указаан уникальный ключ клиента, то он будет сгенерирован автоматически в виде МД5 хєша айдишников ключа и группы

    $ssapi = new SSAPI("api.warder.tk", 9984, "_key_id", ""SeCrEtToKeNvAlUe", "_group_id", "my_custom_key", [15 [, true]]);

    // перезапись ордера, после чего получаем полностью объект изминённый
    $rewritten_order = $ssapi->orders(['id'=>123], [...], SSAPI_RETURN_RESULT | SSAPI_FULL_REWRITE);
    if($rewritten_order->status)
        echo('error: ', $neworder->reason);
    else
        var_dump($rewritten_order->data);

    // добавление инфы в ордер, при это не ждём от апи ответа
    $ssapi->orders(['id'=>123], [...], SSAPI_NO_WAIT_RESPONSE);

    // создаём ордер, и получаем только его id в качестве ответа
    $new_order = $ssapi->orders(NULL, [...], SSAPI_FULL_REWRITE);

    if($neworder->status)
        echo('error: ', $neworder->reason);
    else
        var_dump($neworder->data['id']);