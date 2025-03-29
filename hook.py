
import re
from mitmproxy import http
import psycopg2
import json
import os
from multidict import MultiDict

class BlockResource:
    def __init__(self):
        self.stmt = psycopg2.connect(
            host=os.environ.get("DB_HOST"),
            database=os.environ.get("DB_NAME"),
            user=os.environ.get("DB_USERNAME"),
            password=os.environ.get("DB_PASSWD")
        )

    def _convert_mitm_multidic_json(self, query):
        query_dict = MultiDict(query)
        query_json = json.dumps({key: query_dict.getall(key) for key in query_dict.keys()})

        return query_json

    def _debug_flow(self, data: http.HTTPFlow):
        print(f"host: {data.request.host.lower()}")
        # print(f"host: {json.dumps(data.request.query)}")
        print(f"path: {'/'.join(data.request.path_components)}")
        query_json = self._convert_mitm_multidic_json(data.request.query)
        print(f"query: {query_json}")
        
    
    def db_append_data(self, data: http.HTTPFlow):
        target_hosts = data.request.host.lower()

        with self.stmt.cursor() as cur:
            cur.execute("SELECT * FROM public.addr WHERE address = %s", (target_hosts,))
            tmp = cur.fetchall()
            
            if len(tmp) == 0:
                ret = cur.execute("INSERT INTO public.addr (id, address) VALUES (DEFAULT, %s);", (target_hosts,))
                self.stmt.commit()
                
                cur.execute("SELECT * FROM public.addr WHERE address = %s", (target_hosts,))
                tmp = cur.fetchall()
                print(f"[FOUND NEW SITE]: {tmp[0][0]} | {target_hosts}")

                ret = cur.execute("INSERT INTO public.qs_mitm_history (id, addr, path, qs, unix) VALUES (DEFAULT, %s, %s, %s, now());", (
                    tmp[0][0],                                              # foreign key
                    '/'.join(data.request.path_components),                 # path
                    self._convert_mitm_multidic_json(data.request.query)    # json query string
                ))

                self.stmt.commit()
            else:
                print(f"[IDX FOUND] {tmp[0][0]}")

                ret = cur.execute("INSERT INTO public.qs_mitm_history (id, addr, path, qs, unix) VALUES (DEFAULT, %s, %s, %s, now());", (
                    tmp[0][0],                                              # foreign key
                    '/'.join(data.request.path_components),                 # path
                    self._convert_mitm_multidic_json(data.request.query)    # json query string
                ))

                self.stmt.commit()

        
    def request(self, flow: http.HTTPFlow) -> None:
        # if url_pattern.search(flow.request.pretty_url):
        #     flow.response = http.Response.make(
        #         404,  # HTTP status code
        #         b"Blocked",  # Response body
        #         {"Content-Type": "text/html"}  # Headers
        #     )
        # print(f"url -> {flow.request.pretty_url}")
        # self.db_append_data(flow)
        self.db_append_data(flow)

addons = [
    BlockResource()
]
