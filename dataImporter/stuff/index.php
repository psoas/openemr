<?php

//var_dump ($_SERVER);

echo "<table border=\"1\" style=\"border:1px solid blue;\">";
echo "<th>Clef</th><th>Valeur</th>";
foreach ($_SERVER as $key => $value) {
    echo "<tr><td style=\"width:400px;\">" . $key . "</td><td style=\"width:400px;\">" . $value . "</tr>\n";
}
echo "</table>";

?>