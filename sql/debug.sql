# dummy data insert
INSERT INTO 
    public.mitm_fqdn (id, fqdn, blocked)
VALUES
    (DEFAULT, 'google.com', FALSE);

SELECT * FROM public.mitm_fqdn;
SELECT EXISTS(SELECT 1 FROM public.mitm_fqdn WHERE fqdn = 'yt.com')


INSERT INTO 
    public.mitm_path (id, path)
VALUES
    (DEFAULT, 'webhp');


SELECT * FROM public.mitm_path;


INSERT INTO 
    public.mitm_log_history (id, mitm_fqdn_id, mitm_path_id, blocked)
VALUES
    (DEFAULT, '3', '3', TRUE);