-- Active: 1743415274383@@127.0.0.1@5432@mitm_data


-- store fqdn lists
CREATE TABLE public.mitm_fqdn (
    id                      SERIAL PRIMARY KEY,
    fqdn                    varchar(256) UNIQUE CHECK (fqdn = LOWER(fqdn)),
    blocked                 BOOLEAN DEFAULT FALSE NOT NULL
);

-- store all path, including /admin
CREATE TABLE public.mitm_path (
    id                      SERIAL PRIMARY KEY,
    path                    TEXT UNIQUE NOT NULL
);

-- create a link between fqdn, such
-- example.com/admin (blocked) fqdn/path
-- example.com/random (allow)
CREATE TABLE public.mitm_log_history (
    id                      SERIAL PRIMARY KEY,
    mitm_fqdn_id            INT NOT NULL REFERENCES public.mitm_fqdn(id),
    mitm_path_id            INT NOT NULL REFERENCES public.mitm_path(id),
    blocked                 BOOLEAN DEFAULT FALSE NOT NULL
);

-- # this table read only
-- example: 
-- abc.com/some/path 
-- parsed:
--      - fqdn + path
--      - qs: {}
--      - unix: 1 jan 1970
CREATE TABLE public.mitm_static_log (
    id                      SERIAL PRIMARY KEY,
    mitm_log_history_id     INT NOT NULL REFERENCES public.mitm_log_history(id),
    query_string            JSON,
    unix                    timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE public.mitm_regexp (
    id                      SERIAL PRIMARY KEY,
    regexp                  VARCHAR(1024) NOT NULL,
    active                  BOOLEAN DEFAULT TRUE NOT NULL,
    unix                    timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_public_mitm_fqdn ON public.mitm_fqdn(fqdn);
CREATE INDEX idx_public_mitm_path ON public.mitm_path(path);


DROP TABLE public.mitm_regexp;
DROP TABLE public.mitm_static_log;
DROP table public.mitm_log_history;
DROP TABLE public.mitm_fqdn;
DROP TABLE public.mitm_path;
