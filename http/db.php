<?php

class DB_WRAPPER
{
    private $PDO;
    public function __construct()
    {
        $dsn = sprintf(
            'pgsql:host=%s;dbname=%s',
            getenv('DB_HOST') ?: 'localhost',
            getenv('DB_NAME') ?: 'mitm_logs'
        );
        $username = getenv('DB_USERNAME') ?: 'username';
        $password = getenv('DB_PASSWD') ?: 'password';

        $this->PDO = new PDO($dsn, $username, $password);
        $this->PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function recv_all_recent_links(int $limit = 200): array
    {
        $stmt = $this->PDO->prepare(<<<SQL
            SELECT
                ROW_NUMBER () OVER (
                    ORDER BY public.mitm_static_log.id DESC
                ) AS no,
                FORMAT('%s/%s', public.mitm_fqdn.fqdn, public.mitm_path.path) url,
                public.mitm_static_log.query_string as qs,
                public.mitm_static_log.unix as timestamp,
                public.mitm_log_history.blocked as blocked,

                -- for blocking purpose
                public.mitm_log_history.id as mitm_log_history_id, 
                -- for fqdn blocking
                public.mitm_fqdn.id as fqdn_id
            FROM
                public.mitm_static_log
            INNER JOIN public.mitm_log_history
                ON public.mitm_static_log.mitm_log_history_id = public.mitm_log_history.id
            INNER JOIN public.mitm_fqdn
                ON public.mitm_fqdn.id = public.mitm_log_history.mitm_fqdn_id
            INNER JOIN public.mitm_path
                ON public.mitm_path.id = public.mitm_log_history.mitm_path_id
            ORDER BY public.mitm_static_log.unix DESC LIMIT 200;
        SQL);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recv_all_blocked_fqdn(int $limit = 200): array
    {
        $stmt = $this->PDO->prepare(<<<SQL
            SELECT * FROM public.mitm_fqdn WHERE blocked = TRUE LIMIT 200;
        SQL);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function recv_all_blocked_path(int $limit = 200): array
    {
        $stmt = $this->PDO->prepare(<<<SQL
            SELECT
                FORMAT('%s/%s', public.mitm_fqdn.fqdn, public.mitm_path.path) path,
                public.mitm_log_history.blocked,
                public.mitm_log_history.id
            FROM
                public.mitm_log_history
            INNER JOIN public.mitm_fqdn
                ON public.mitm_fqdn.id = public.mitm_log_history.mitm_fqdn_id
            INNER JOIN public.mitm_path
                ON public.mitm_path.id = public.mitm_log_history.mitm_path_id
            WHERE
                public.mitm_log_history.blocked = TRUE;
        SQL);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function block_mitm_log_history_by(int $id): bool
    {
        $stmt = $this->PDO->prepare(<<<SQL
            UPDATE public.mitm_log_history SET blocked = TRUE WHERE id = :id;
        SQL);

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function block_fqdn_by(int $id): bool
    {
        $stmt = $this->PDO->prepare(<<<SQL
            UPDATE public.mitm_fqdn SET blocked = TRUE WHERE id = :id;
        SQL);

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function unblock_mitm_log_history_by(int $id): bool
    {
        $stmt = $this->PDO->prepare(<<<SQL
            UPDATE public.mitm_log_history SET blocked = FALSE WHERE id = :id;
        SQL);

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    public function unblock_fqdn_by(int $id): bool
    {
        $stmt = $this->PDO->prepare(<<<SQL
            UPDATE public.mitm_fqdn SET blocked = FALSE WHERE id = :id;
        SQL);

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    public function get_db_state(): array {
        $stmt = $this->PDO->prepare(<<<SQL
            SELECT COUNT(*) FROM public.mitm_fqdn
        SQL);
        $stmt->execute();
        $fqdn_count = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $stmt = $this->PDO->prepare(<<<SQL
            SELECT COUNT(*) FROM public.mitm_path
        SQL);
        $stmt->execute();
        $path_count = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $stmt = $this->PDO->prepare(<<<SQL
            SELECT COUNT(*) FROM public.mitm_log_history
        SQL);
        $stmt->execute();
        $mitm_log_history_count = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $stmt = $this->PDO->prepare(<<<SQL
            SELECT COUNT(*) FROM public.mitm_static_log
        SQL);
        $stmt->execute();
        $static_log_count = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $stmt = $this->PDO->prepare(<<<SQL
            SELECT pg_size_pretty( pg_database_size(:dbname) );
        SQL);
        $stmt->execute([
            "dbname" => getenv('DB_NAME') ?: 'mitm_logs'
        ]);
        $db_size = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            "fqdn_count" => $fqdn_count[0]["count"],
            "path_count" => $path_count[0]["count"],
            "mitm_log_history_count" => $mitm_log_history_count[0]["count"],
            "static_log_count" => $static_log_count[0]["count"],
            "db_size" => $db_size[0]["pg_size_pretty"]
        ];
    }
}
