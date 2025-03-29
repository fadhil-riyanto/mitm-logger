
CREATE TABLE public.addr (
    id                  SERIAL PRIMARY KEY,
    address             varchar(4096),
    blocked             BOOLEAN
);


CREATE TABLE public.qs_mitm_history (
    id                  SERIAL PRIMARY KEY,
    addr                INT REFERENCES public.addr(id),
    path                TEXT,
    qs                  JSON,
    unix                timestamp,
    blocked             BOOLEAN NULL
)

DROP TABLE public.qs_mitm_history;
DROP table public.addr;