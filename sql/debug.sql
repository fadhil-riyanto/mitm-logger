# do not run
# debug purpose
SELECT * FROM pg_roles;

GRANT ALL PRIVILEGES ON DATABASE mitm_log TO fadhil_riyanto;
GRANT ALL ON SCHEMA public TO fadhil_riyanto;
GRANT USAGE ON SCHEMA pg_catalog TO fadhil_riyanto;

SELECT * FROM pg_roles;

SELECT grantee, privilege_type
FROM information_schema.role_schema_grants
WHERE schema_name = 'public'
  AND grantee = 'fadhil_riyanto';

SELECT now();


INSERT INTO public.addr (id, address) VALUES (DEFAULT, 'GOOGLE.COM');
SELECT * FROM public.addr;


SELECT * FROM public.addr WHERE address = 'www.fadev.org';


SELECT 
    public.addr.address, 
    public.qs_mitm_history.path 
FROM
    public.addr 
INNER JOIN 
    public.qs_mitm_history ON 
        public.addr.id = public.qs_mitm_history.addr 
    ORDER BY 
        public.qs_mitm_history.unix
    DESC;