# install
## 1. run this query as sql setup (only pgsql)
```sql
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
```

## cloning & chmod
```sh
git clone https://github.com/fadhil-riyanto/mitm-logger.git
cd mitm-logger
chmod 777 run.sh
```

## env
please rename .env.example to .env, and fill it

# notes
- HTTP proxy running on port 8081
- web interface running on port 12343

# testing & certificate stuff
```sh
curl --proxy http://127.0.0.1:8081 --cacert ./mitmproxy-ca-cert.pem https://duckduckgo.com/?t=h_&q=what+is+water&ia=web
```
as you can see, curl need --cacert, without this, mitm cannot see the underlying data

# web interface 

<a href="https://ibb.co.com/hRz3djMt"><img src="https://i.ibb.co.com/MD0F14h3/300.png" alt="300" border="0"></a>

# license
MIT