<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
</head>
<style>
    body {
        margin: 20px
    }

    .title-mitm {
        text-align: center;
    }

    .col {
        border: 1px solid #000;
        padding: 20px;
    }

    a.mitmbutton {
        padding: 1px 6px;
        border: 1px outset buttonborder;
        border-radius: 3px;
        color: buttontext;
        background-color: buttonface;
        text-decoration: none;
    }

    .block_domain_clr {
        background-color: red;
    }

    .block_path_clr {
        background-color: blue;
    }

    .bar {
        display: flex;
    }

    .simplefex {
        display: flex;
        margin: 10px;
        gap: 10px;
    }

    .tbl-wordrwap {
        word-wrap: break-word;
    }

    .darkblue {
        color: blue;
    }
</style>

<body>
    <?php
    // this is hooks
    try {
        $pdo = new PDO(
            sprintf(
                'pgsql:host=%s;dbname=%s',
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_NAME') ?: 'mitm_logs'
            ),
            getenv('DB_USERNAME') ?: 'username',
            getenv('DB_PASSWD') ?: 'password'
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }

    if (isset($_GET['regexp_block'])) {
        $stmt = $pdo->prepare("
            INSERT INTO 
                public.mitm_regexp_blocking (id, regexp, active)
            VALUES 
                (DEFAULT, :regexp, TRUE);
        ");
        $stmt->execute([
            "regexp" => $_GET["regexp_block"]
        ]);
    }

    if (isset($_GET["block_domain_id"])) {
        $stmt = $pdo->prepare("
            UPDATE public.addr
            SET blocked = TRUE
            WHERE id = :block_domain_id
        ");
        $stmt->execute([
            "block_domain_id" => isset($_GET["block_domain_id"]) ? $_GET["block_domain_id"] : false
        ]);
    }

    if (isset($_GET["block_path_id"])) {
        $stmt = $pdo->prepare("
            UPDATE public.qs_mitm_history
            SET blocked = TRUE
            WHERE id = :block_domain_path
        ");
        $stmt->execute([
            "block_domain_path" => isset($_GET["block_path_id"]) ? $_GET["block_path_id"] : false
        ]);
    }

    if (isset($_GET["clear_domain"]) && isset($_GET["clear_path"])) {
        // echo "\"" . $_GET["clear_path"] == "" . "\"";
        if ($_GET["clear_domain"] != "" && $_GET["clear_path"] != "") {
            $stmt = $pdo->prepare("
                UPDATE public.addr
                SET blocked = FALSE
                WHERE id = :clear_id
            ");
            $stmt->execute([
                "clear_id" => isset($_GET["clear_domain"]) ? $_GET["clear_domain"] : false
            ]);

            $stmt = $pdo->prepare("
                UPDATE public.qs_mitm_history
                SET blocked = FALSE
                WHERE id = :clear_id
            ");
            $stmt->execute([
                "clear_id" => isset($_GET["clear_path"]) ? $_GET["clear_path"] : false
            ]);
        } else if ($_GET["clear_path"] == "") {
            // only domain
            $stmt = $pdo->prepare("
                UPDATE public.addr
                SET blocked = FALSE
                WHERE id = :clear_id
            ");
            $stmt->execute([
                "clear_id" => isset($_GET["clear_domain"]) ? $_GET["clear_domain"] : false
            ]);
        } else if ($_GET["clear_domain"] == "") {
            // only path
            $stmt = $pdo->prepare("
                UPDATE public.qs_mitm_history
                SET blocked = FALSE
                WHERE id = :clear_id
            ");
            $stmt->execute([
                "clear_id" => isset($_GET["clear_path"]) ? $_GET["clear_path"] : false
            ]);
        }
    } else if (isset($_GET["change_regexp_state_deactivate"])) {
        $stmt = $pdo->prepare("
            UPDATE public.mitm_regexp_blocking
            SET active = FALSE
            WHERE id = :change_regexp_state_deactivate
        ");
        $stmt->execute([
            "change_regexp_state_deactivate" => isset($_GET["change_regexp_state_deactivate"]) ? $_GET["change_regexp_state_deactivate"] : false
        ]);
    } else if (isset($_GET["change_regexp_state_activate"])) {
        $stmt = $pdo->prepare("
            UPDATE public.mitm_regexp_blocking
            SET active = TRUE
            WHERE id = :change_regexp_state_activate
        ");
        $stmt->execute([
            "change_regexp_state_activate" => isset($_GET["change_regexp_state_activate"]) ? $_GET["change_regexp_state_activate"] : false
        ]);
    }

    ?>
    <h1 class="title-mitm">Emergency MITM logger dashboard</h1>

    <div class="col">
        <div class="bar">
            <a class='darkblue' href="/">all</a> |
            <a class='darkblue' href="/?search=list_blocked_domain&limit=200">list_blocked_domain</a> |
            <a class='darkblue' href="/?search=list_blocked_path&limit=200">list_blocked_path</a> |
            <a class='darkblue' href="/?search=list_blocked_regexp&limit=200">list_blocked_regexp</a> | 
            <a class='darkblue' href="/?server_stats=1">server_stats</a>


        </div>
        <hr>
        <div class="simplefex">
            <div>
                <h3>query modifier</h3>
                <form action="" method="get">
                    <label for="numberInput">LIMIT :</label>
                    <input type="number" id="numberInput" name="limit" min="0" max="1000" value="200">
                    <br>public.addr
                    <a>search by domain (newest)</a>
                    <br>
                    <input type="text" name="by_domain" placeholder="random.com"></input>
                    <input type="text" name="by_path" placeholder="some/random"></input>
                    <button type="submit">execute</button>
                </form>
            </div>
            <div>
                <h3>block</h3>
                <form action="" method="get">

                    <input type="text" name="regexp_block" placeholder="^https:\/\/somesite\/path"></input>
                    <button type="submit">execute</button>
                </form>
            </div>
        </div>
    </div>

    
    <?php
    if (isset($_GET["server_stats"])) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM public.addr
        ");
        $stmt->execute([]);
        $addr_count = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM public.qs_mitm_history
        ");
        $stmt->execute([]);
        $history_mitm_count = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT pg_size_pretty( pg_database_size(:dbname) );
        ");
        $stmt->execute([
            "dbname" => getenv('DB_NAME') ?: 'mitm_logs'
        ]);
        $db_size = $stmt->fetchAll(PDO::FETCH_ASSOC);
        

        echo "<div class=\"col\">";
        echo "addr count(*): " . $addr_count[0]["count"] . "<br>";
        echo "mitm_history count(*): " . $history_mitm_count[0]["count"] . "<br>";
        echo "db size: " . $db_size[0]["pg_size_pretty"] . "<br>";
        
    
        echo "</div>";

        exit();
    }
    ?>


    <div class="col">
        <h3>recent log (200 entries)</h3>

        <table border="1" width="100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>domain/subdomain/regexp</th>
                    <th>act</th>
                    <th>path/regexstate</th>
                    <th>QUERY STRING</th>

                </tr>
            </thead>
            <tbody>
                <?php


                // Fetch logs from the database
                try {

                    if (isset($_GET['search'])) {
                        if ($_GET['search'] == 'list_blocked_domain') {
                            $stmt = $pdo->prepare(
                                "
                                SELECT 
                                    public.addr.id, 
                                    public.addr.address, 
                                    public.addr.blocked,
                                    public.addr.blocked as domain_block,
                                    public.addr.id as addr_id
                                FROM 
                                    public.addr
                                WHERE
                                    blocked = TRUE
                                LIMIT :limit;
                                "
                            );
                        } else if ($_GET['search'] == 'list_blocked_path') {
                            $stmt = $pdo->prepare(
                                "
                                SELECT 
                                    public.qs_mitm_history.id,
                                    public.qs_mitm_history.path, 
                                    public.qs_mitm_history.qs,

                                    public.qs_mitm_history.id as path_id,
                                    public.qs_mitm_history.blocked as path_block_bool
                                FROM 
                                    public.qs_mitm_history
                                WHERE
                                    blocked = TRUE
                                LIMIT :limit;
                                "
                            );
                        } else if ($_GET['search'] == 'list_blocked_regexp') {
                            $stmt = $pdo->prepare(
                                "
                                SELECT 
                                    public.mitm_regexp_blocking.id,
                                    public.mitm_regexp_blocking.regexp as regexp,
                                    public.mitm_regexp_blocking.active
                                FROM 
                                    public.mitm_regexp_blocking
                                LIMIT :limit;
                                "
                            );
                        } 
                    } else {

                        $stmt = $pdo->prepare("
                        SELECT 
                            public.addr.address, 
                            public.qs_mitm_history.path, 
                            public.qs_mitm_history.qs,
    
                            public.addr.id as addr_id,
                            public.qs_mitm_history.id as path_id,
    
                            public.addr.blocked as domain_block,
                            public.qs_mitm_history.blocked as path_block_bool
                            
                            
                        FROM 
                            public.addr
                        INNER JOIN 
                            public.qs_mitm_history 
                        ON 
                            public.addr.id = public.qs_mitm_history.addr
                        ORDER BY 
                            public.qs_mitm_history.unix DESC NULLS LAST
                        LIMIT :limit;
                        ");
                    }
                    $stmt->execute([
                        "limit" => isset($_GET["limit"]) ? $_GET["limit"] : 200
                    ]);
                    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    die("Error fetching logs: " . $e->getMessage());
                }

                // var_dump($logs);
                foreach ($logs as $index => $log) {

                    echo "<tr>";
                    echo "<td>" . ($index + 1) . "</td>";

                    if (isset($log['regexp'])) {
                        echo "<td>" . htmlspecialchars($log['regexp']) . "</td>";
                    } else {
                        if ($log["domain_block"] === true) {
                            // echo "<td>" . $log["domain_block"] . "</td>";
                            echo "<td class='block_domain_clr'>" . htmlspecialchars($log['address']) . "</td>";
                        } else {
                            // echo "<td>" . $log["domain_block"] . "</td>";
                            echo "<td>" . htmlspecialchars($log['address']) . "</td>";
                        }
                    }


                    if (isset($log['regexp'])) {
                        echo "<td>" .
                            "<a href=\"?change_regexp_state_deactivate=" . $log["id"] . "\" class=\"mitmbutton\">deactivate rule</a>" .
                            "<a href=\"?change_regexp_state_activate=" . $log["id"] . "\" class=\"mitmbutton\">activate rule</a>" .
                            "</td>";
                    } else {
                        echo "<td>" .
                            "<a href=\"?block_domain_id=" . $log["addr_id"] . "\" class=\"mitmbutton\">block_domain</a>" .
                            "<a href=\"?block_path_id=" . $log["path_id"] . "\" class=\"mitmbutton\">block_path_and_domain</a>" .
                            "<a href=\"?clear_domain=" . $log["addr_id"] . "&clear_path=" . $log["path_id"] . "\" class=\"mitmbutton\">cls</a>" .
                            "</td>";
                    }

                    if (isset($log['regexp'])) {
                        echo "<td class='tbl-wordrwap'>" . htmlspecialchars($log['active']) . "</td>";
                    } else {
                        if ($log["path_block_bool"] === true) {
                            // echo "<td>" . $log["domain_block"] . "</td>";
                            echo "<td class='block_path_clr tbl-wordrwap'>" . htmlspecialchars($log['path']) . "</td>";
                        } else {
                            // echo "<td>" . $log["domain_block"] . "</td>";
                            echo "<td class='tbl-wordrwap'>" . htmlspecialchars($log['path']) . "</td>";
                        }
                    }
                    // echo "<td>" . htmlspecialchars($log['path']) . "</td>";
                    echo "<td>" . $log['qs'] . "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>

</html>