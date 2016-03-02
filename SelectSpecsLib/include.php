<?php

$dir = dirname(__FILE__).'/lib/Tivoka';

include $dir . '/Tivoka.php';
include $dir . '/Client.php';

include $dir . '/Exception/Exception.php';
include $dir . '/Exception/ConnectionException.php';
include $dir . '/Exception/RemoteProcedureException.php';
include $dir . '/Exception/SpecException.php';
include $dir . '/Exception/SyntaxException.php';
include $dir . '/Exception/ProcedureException.php';
include $dir . '/Exception/InvalidParamsException.php';

include $dir . '/Client/Connection/ConnectionInterface.php';
include $dir . '/Client/Connection/AbstractConnection.php';
include $dir . '/Client/Connection/Tcp.php';
include $dir . '/Client/Request.php';
include $dir . '/Client/Notification.php';
include $dir . '/Client/BatchRequest.php';
include $dir . '/Client/NativeInterface.php';

$ssdir = dirname(__FILE__);

include $ssdir . '/ssapi/flags.php';
include $ssdir . '/ssapi/logger.php';
include $ssdir . '/ssapi/ssapi.php';
include $ssdir . '/config_groups.php';

if(!isset($GROUPS)) $GROUPS=[];

?>