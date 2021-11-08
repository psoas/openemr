<?php
class foo_mysqli extends mysqli {
    public function __construct($host, $user, $pass, $db) {
        parent::__construct($host, $user, $pass, $db);

        if (mysqli_connect_error()) {
            die('Connect Error (' . mysqli_connect_errno() . ') '
                    . mysqli_connect_error());
        }
    }

}

$db = new foo_mysqli('localhost', 'root', 'EMR#$%', 'openemr6');

echo 'Success... ' . $db->host_info . "\n";
$resultSet1 = $db->query("show tables");
while($rowValue1 = $resultSet1->fetch_row()) {

$resultSet2 = $db->query("DESCRIBE ".$rowValue1[0] );
echo "$rowValue1[0], ";
while($rowValue = $resultSet2->fetch_row()) {
foreach ($rowValue as $key => $value2) {

						
						echo "$value2" .", ";
						//break;
					}
					echo "\n";
					}
					
					}
$db->close();
?>