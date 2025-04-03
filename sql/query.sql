SELECT
    ROW_NUMBER () OVER (
        ORDER BY public.mitm_static_log.unix
    ) AS no,
    FORMAT('%s/%s', public.mitm_fqdn.fqdn, public.mitm_path.path) url,
    public.mitm_static_log.query_string as qs,
    public.mitm_static_log.unix as timestamp,
    public.mitm_log_history.blocked
FROM
    public.mitm_static_log
INNER JOIN public.mitm_log_history
    ON public.mitm_static_log.mitm_log_history_id = public.mitm_log_history.id
INNER JOIN public.mitm_fq{"some": ["data"], "and": ["lol"]}dn
    ON public.mitm_fqdn.id = public.mitm_log_history.mitm_fqdn_id
INNER JOIN public.mitm_path
    ON public.mitm_path.id = public.mitm_log_history.mitm_path_id
ORDER BY public.mitm_static_log.unix ASC LIMIT 200;

SELECT * FROM public.mitm_fqdn WHERE blocked = FALSE LIMIT 200;

SELECT
    FORMAT('%s/%s', public.mitm_fqdn.fqdn, public.mitm_path.path) path,
    public.mitm_log_history.blocked
FROM
    public.mitm_log_history
INNER JOIN public.mitm_fqdn
    ON public.mitm_fqdn.id = public.mitm_log_history.mitm_fqdn_id
INNER JOIN public.mitm_path
    ON public.mitm_path.id = public.mitm_log_history.mitm_path_id
WHERE
    public.mitm_log_history.blocked = TRUE