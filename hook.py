
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

        self.cur = self.stmt.cursor();

        self.g_program_counter = 0

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

    def intercept_stop_req(self, flow: http.HTTPFlow): 
        flow.response = http.Response.make(
            404,  # HTTP status code
            b"Blocked",  # Response body
            {"Content-Type": "text/html"}  # Headers
        )
        
    def db_append_data(self, flow: http.HTTPFlow):
        fqdn = flow.request.host.lower()
        path = '/'.join(flow.request.path_components)

    
        # check whatever domain already present
        self.cur.execute("SELECT * FROM public.mitm_fqdn WHERE fqdn = %s LIMIT 1", (fqdn,))
        fqdn_data = self.cur.fetchall();

        # fqdn not found
        # path not found, do insert
        if len(fqdn_data) == 0:
            ret = self.cur.execute("""INSERT INTO 
                                    public.mitm_fqdn (id, fqdn, blocked) 
                                VALUES (DEFAULT, %s, FALSE);""", (fqdn,))
            self.stmt.commit()
            print(f"append fqdn: {fqdn}")

            # inserting & checking path
            self.cur.execute("SELECT * FROM public.mitm_path WHERE path = %s LIMIT 1", (path,))
            mitm_path_data = self.cur.fetchall();

            if len(mitm_path_data) == 0:
                ret = self.cur.execute("""INSERT INTO 
                                        public.mitm_path (id, path) 
                                    VALUES (DEFAULT, %s);""", (path,))
                print(f"append fqdn on path: {path}")
                self.stmt.commit()

        else:
            # fqdn found, idk about path
            self.cur.execute("SELECT * FROM public.mitm_path WHERE path = %s LIMIT 1", (path,))
            mitm_path_data = self.cur.fetchall();

            if len(mitm_path_data) == 0:
                ret = self.cur.execute("""INSERT INTO 
                                        public.mitm_path (id, path) 
                                    VALUES (DEFAULT, %s);""", (path,))
                print(f"append path: {path}")
                self.stmt.commit()


        self.cur.execute("SELECT * FROM public.mitm_fqdn WHERE fqdn = %s LIMIT 1", (fqdn,))
        fqdn_data = self.cur.fetchall();

        self.cur.execute("SELECT * FROM public.mitm_path WHERE path = %s LIMIT 1", (path,))
        mitm_path_data = self.cur.fetchall();


        # find whatever data found first
        self.cur.execute("""SELECT 
                            *
                        FROM 
                            public.mitm_log_history
                        WHERE
                            mitm_fqdn_id = %s AND 
                            mitm_path_id = %s 
                        LIMIT 1""", (fqdn_data[0][0], mitm_path_data[0][0],))
        mitm_log_history_data = self.cur.fetchall();
        
        if len(mitm_log_history_data) == 0:
            ret = self.cur.execute("""INSERT INTO 
                                        public.mitm_log_history (id, mitm_fqdn_id, mitm_path_id, blocked) 
                                    VALUES (DEFAULT, %s, %s, FALSE);""", (
                                                fqdn_data[0][0],
                                                mitm_path_data[0][0],))
            print(f"append history_data (fqdn, path) id: {fqdn_data[0][0]}, {mitm_path_data[0][0]}")

            # syncing
            self.stmt.commit()

            # re-update mitm_log_history_data
            self.cur.execute("""SELECT 
                                *
                            FROM 
                                public.mitm_log_history
                            WHERE
                                mitm_fqdn_id = %s AND 
                                mitm_path_id = %s 
                            LIMIT 1""", (fqdn_data[0][0], mitm_path_data[0][0],))
            mitm_log_history_data = self.cur.fetchall();

        # global insert mitm_static_log
        ret = self.cur.execute("""INSERT INTO 
                                public.mitm_static_log (id, mitm_log_history_id, query_string, unix) 
                            VALUES (DEFAULT, %s, %s, now());""", (
                                        mitm_log_history_data[0][0],
                                        self._convert_mitm_multidic_json(flow.request.query),))

        print(f"append static log mitm_log_history_id: {mitm_log_history_data[0][0]}")
        
        self.stmt.commit()

        #     # insert static log
        #     cur.execute("""SELECT 
        #                     * 
        #                 FROM
        #                     public.mitm_static_log 
        #                 WHERE
        #                     mitm_fqdn_id = %s AND 
        #                     mitm_path_id = %s
        #                 LIMIT 1""", (fqdn_data[0][0], mitm_path_data[0][0], ))
        #     mitm_static_log_data = cur.fetchall();

        #     if len(mitm_static_log_data) == 0:
        #         ret = cur.execute("""INSERT INTO 
        #                                     public.mitm_static_log (id, mitm_log_history_id, query_string, unix) 
        #                                 VALUES (DEFAULT, %s, %s, FALSE);""", (
        #                                             fqdn_data[0][0],
        #                                             mitm_path_data[0][0],))
        #         self.stmt.commit()
        # else:

        



    def do_filter(self, flow: http.HTTPFlow):
        fqdn = flow.request.host.lower()
        path = '/'.join(flow.request.path_components)

        self.cur.execute("SELECT * FROM public.mitm_fqdn WHERE fqdn = %s LIMIT 1", (fqdn,))
        data = self.cur.fetchall();

        fqdn_found = len(data)
        if fqdn_found == 1:
            if data[0][2] == True:              # repr blocked == true
                print(f"[BLOCKED] {data[0][1]}")
                self.intercept_stop_req(flow)
                return True

        # check whatever blocked by path, query on public.mitm_log_history
        self.cur.execute("SELECT * FROM public.mitm_path WHERE path = %s LIMIT 1", (path,))
        mitm_path_data = self.cur.fetchall();

        mitm_path_found = len(mitm_path_data)

        if mitm_path_found == 1 and fqdn_found == 1:
            mitm_fqdn_id = data[0][0]
            mitm_path_id = mitm_path_data[0][0]

            print(f"fqdn {mitm_fqdn_id}, path {mitm_path_id}")
            self.cur.execute("""SELECT EXISTS(SELECT 
                            1 
                        FROM 
                            public.mitm_log_history 
                        WHERE 
                            mitm_fqdn_id = %s AND 
                            mitm_path_id = %s AND
                            blocked      = TRUE
                        LIMIT 1)""", (mitm_fqdn_id, mitm_path_id, ))

            data = self.cur.fetchall()
            mitm_log_history_found = data[0][0]

            if mitm_log_history_found == True:
                self.intercept_stop_req(flow)
                return True

        return False
        
    def request(self, flow: http.HTTPFlow) -> None:
        # if url_pattern.search(flow.request.pretty_url):
        #     
        # print(f"url -> {flow.request.pretty_url}")
        # self.db_append_data(flow)
        # self._debug_flow(flow)
        try:
            ret = self.do_filter(flow)

            if ret == False:
                self.db_append_data(flow)
        except psycopg2.errors.InFailedSqlTransaction:
            self.stmt.rollback()
        # if self.g_program_counter % 20 == 0:
        #     # avoid re-commit
        #     self.stmt.commit()
        

addons = [
    BlockResource()
]
