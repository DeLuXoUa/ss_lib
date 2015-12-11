#Создание объекта (инициализация коннекта)
$ssapi = new SSAPI($host, $port, $auth_token, $auth_group, $timeout = 15, $notify_mode = true, , $encription = false)
$host - урл сервера
$port - порт сервера
$auth_token - секретный ключ
$auth_group айди группы по умолчанию
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

### SSAPI::items($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет айтем в апи.

### SSAPI::order_items($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет айтем в ордере в апи.

### SSAPI::items_images($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет картинки в айтемах в апи.

### SSAPI::groups($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет группы в апи.

### SSAPI::users($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет юзеров в апи.

### SSAPI::profiles($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет профили в апи.

### SSAPI::item_translations($search, $data, $flags, $options)
Заменяет(обновляет) или дополняет перевод для айтема в апи.


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

//  NodeAPI($host, $port, $auth_token, $auth_group, $timeout = 15, $notify_mode = true)
//два последних параметра не обязательные

    $ssapi = new SSAPI("api.warder.tk", 9984, "SeCrEtToKeNvAlUe", "GrOuPiD", [15 [, true]]);

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