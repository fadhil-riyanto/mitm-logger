
CREATE TABLE public.addr (
    id                  SERIAL PRIMARY KEY,
    address             varchar(4096)
);


CREATE TABLE public.qs_mitm_history (
    id                  SERIAL PRIMARY KEY,
    addr                INT REFERENCES public.addr(id),
    path                TEXT,
    qs                  JSON,
    unix                timestamp
)