<?php

define('SSAPI_RETURN_RESULT', 1 << 0); //return full documents after finish query, not only id
define('SSAPI_NO_WAIT_RESPONSE', 1 << 1); //no waiting for response from api server
define('SSAPI_FULL_REWRITE', 1 << 2); //full rewrite of document (delete all old data and writing new)
define('SSAPI_CREATE_IF_NOT_EXIST', 1 << 3); //if cant find document by search params - create new
define('SSAPI_DELETE_DOCUMENT', 1 << 4); //delete document
define('SSAPI_ONLY_IN_GROUP', 1 << 5); //all operations only in selected group
define('SSAPI_MULTI_QUERY', 1 << 6); //multiple query transactions
define('SSAPI_TASK_FOR_JOB_SERVER', 1 << 7); //tasks for jobs server

define('SSAPI_CONNECTION_NOTIFYS_ENABLE', 1 << 0); //notifys mode enable
define('SSAPI_CONNECTION_NOTIFYS_DISABLE', 0 << 0); //notifys mode disable
define('SSAPI_CONNECTION_ENCRIPTION_ENABLE', 1 << 1); //encription data transer enable
define('SSAPI_CONNECTION_ENCRIPTION_DISABLE', 0 << 1); //encription data transer disable

?>