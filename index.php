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

        
    }


    ?>
    <h1 class="title-mitm">Emergency MITM logger dashboard</h1>

    <div class="col">
        <h3>query modifier</h3>
        <form action="" method="get">
            <label for="numberInput">LIMIT :</label>
            <input type="number" id="numberInput" name="limit" min="0" max="1000" value="200">
            <br>public.addr
            <a>search by domain (newest)</a>
            <br>
            <input type="text" name="by_domain" placeholder="random.com"></input>
            <button type="submit">execute</button>
        </form>
    </div>

    <div class="col">
        <h3>recent log (200 entries)</h3>

        <table border="1" width="100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>domain/subdomain</th>
                    <th>act</th>
                    <th>path</th>
                    <th>QUERY STRING</th>

                </tr>
            </thead>
            <tbody>
                <?php


                // Fetch logs from the database
                try {
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

                    if ($log["domain_block"] === true) {
                        // echo "<td>" . $log["domain_block"] . "</td>";
                        echo "<td class='block_domain_clr'>" . htmlspecialchars($log['address']) . "</td>";
                    } else {
                        // echo "<td>" . $log["domain_block"] . "</td>";
                        echo "<td>" . htmlspecialchars($log['address']) . "</td>";
                    }
                    echo "<td>" .
                        "<a href=\"?block_domain_id=" . $log["addr_id"] . "\" class=\"mitmbutton\">block_domain</a>" .
                        "<a href=\"?block_path_id=" . $log["path_id"] . "\" class=\"mitmbutton\">block_path_and_domain</a>" .
                        "<a href=\"?clear_domain=" . $log["addr_id"] . "&clear_path=" . $log["path_id"]. "\" class=\"mitmbutton\">cls</a>" .
                        "</td>";

                        if ($log["path_block_bool"] === true) {
                            // echo "<td>" . $log["domain_block"] . "</td>";
                            echo "<td class='block_path_clr'>" . htmlspecialchars($log['path']) . "</td>";
                        } else {
                            // echo "<td>" . $log["domain_block"] . "</td>";
                            echo "<td>" . htmlspecialchars($log['path']) . "</td>";
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