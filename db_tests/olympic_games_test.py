#!/usr/bin/python
# -*- coding: utf-8 -*-
from SPARQLWrapper import SPARQLWrapper, JSON
import MySQLdb as mdb
import sys
import traceback
import re


sparql = SPARQLWrapper("http://dbpedia.org/sparql")
con = mdb.connect('localhost', 'root', '', 'db_project_test',unix_socket = '/opt/lampp/var/mysql/mysql.sock')


def query_and_insert_athlete_field_and_games():
    with con:
        cur = con.cursor()
        # cur.execute("TRUNCATE TABLE AthleteSportField")
        # cur.execute("TRUNCATE TABLE AthleteGames")
        # cur.execute("TRUNCATE TABLE SportField")
        # con.commit()
        offset = 0
        while True:
            print offset
            if offset > 10000: # todo: remove this
                break
            athlete_games_list = get_athletes_games(offset)
            if athlete_games_list:
                offset += len(athlete_games_list)
                insert_athletes_games_and_field(athlete_games_list, con)
            else:
                break


def get_athletes_games(offset):
    athlete_games_list = []

    query_offset_string = "PREFIX dbpedia0: <http://dbpedia.org/ontology/> " \
        "PREFIX dct: <http://purl.org/dc/terms/> " \
        "SELECT ?personlabel ?gamelabel WHERE { " \
        "?sw a dbpedia0:Athlete. " \
        "?sw dbpedia0:birthDate ?bd. " \
        "?sw rdfs:label ?personlabel. " \
        "?sw dct:subject/rdfs:label ?gamelabel. " \
        "FILTER(lang(?personlabel) = 'en') " \
        "FILTER(lang(?gamelabel) = 'en') " \
        "FILTER regex(?gamelabel, '^.* at the [1-2][0-9][0-9][0-9] (summer|winter) Olympics$', 'i') " \
        "}" \
        "limit 1000 offset %s" % offset

    sparql.setQuery(query_offset_string)

    sparql.setReturnFormat(JSON)
    results = sparql.query().convert()
    results = results["results"]["bindings"]
    for res in results:
        tup = (res["personlabel"]["value"], res["gamelabel"]["value"])
        athlete_games_list.append(tup)

    return athlete_games_list


def insert_athletes_games_and_field(athlete_game_tuple, con):
    for tup in athlete_game_tuple:
        athlete_label = tup[0].encode('latin-1', 'ignore')
        athlete_game = tup[1].encode('latin-1', 'ignore')
        p = re.compile('(.*) at the (\d{4}) (summer|winter) Olympics', re.IGNORECASE)
        match = p.match(athlete_game)
        if not match:
            continue
        field = match.group(1)
        year = match.group(2)
        season = match.group(3)
        cur = con.cursor()

        try:
            #insert into sport field
            cur.execute("INSERT IGNORE INTO SportField (field_name) VALUES (%s)", [field])
            con.commit()

            # insert into athlete field - this will fail if athlete is not found
            # field is necessarily found because
            cur.execute("INSERT IGNORE INTO AthleteSportFields (athlete_id, field_id) "
                        "SELECT a.athlete_id, f.field_id "
                        "FROM athlete a, SportField f "
                        "WHERE a.dbp_label = %s AND f.field_name = %s ",
                        (athlete_label, field))
            con.commit()

            cur.execute("INSERT IGNORE INTO AthleteGames (athlete_id, game_id) "
                        "SELECT a.athlete_id, g.game_id "
                        "FROM athlete a, olympicgame g "
                        "WHERE a.dbp_label = %s AND g.year = %s AND g.season = %s",
                        (athlete_label, year, season))
            con.commit()

        except:
            traceback.print_exc(file=sys.stdout)
            con.rollback()



def get_olympic_years():
    sparql.setQuery("""
    PREFIX dbpedia0: <http://dbpedia.org/ontology/>

    SELECT ?label WHERE {
    ?og a dbpedia0:Olympics.
    ?og rdfs:label ?label.
    FILTER(lang(?label) = "en")
    FILTER regex(?label, '^[1-2][0-9][0-9][0-9] .* olympics' , 'i')
    }
    ORDER BY ?label
    """)
    sparql.setReturnFormat(JSON)
    results = sparql.query().convert()

    results = results["results"]["bindings"]
    olympic_games = []

    for res in results:
        olympic_game_arr = res["label"]["value"].split(' ')
        olympic_games.append((olympic_game_arr[0].lower(), olympic_game_arr[1].lower()))

    return olympic_games


def query_and_insert_athletes():
    with con:
        cur = con.cursor()

        cur.execute("TRUNCATE TABLE athlete;")
        con.commit()
        offset = 0;
        while True:
            print offset
            if offset > 10000:
                break
            athlete_list = get_athletes(offset)
            if athlete_list:
                offset += len(athlete_list)
                insert_athletes(athlete_list, cur)
            else:
                break


def get_athletes(offset):
    athlete_list = []

    query_offset_string = "PREFIX dbpedia0: <http://dbpedia.org/ontology/> " \
        "SELECT ?label ?bd (group_concat(?bp; separator = ', ' as ?bpl)) as ?bpn ?comment WHERE { " \
        "?at a dbpedia0:Athlete. " \
        "?at rdfs:label ?label. " \
        "?at dbpedia0:birthDate ?bd. " \
        "?at dbpedia0:birthPlace/rdfs:label ?bp. " \
        "?at rdfs:comment ?comment " \
        "FILTER(lang(?label) = 'en') " \
        "FILTER(datatype(?bd) = xsd:date) " \
        "FILTER(lang(?bp) = 'en')" \
        "FILTER(lang(?comment) = 'en') " \
        "} " \
        "limit 1000 offset %s" % (offset)

    sparql.setQuery(query_offset_string)

    sparql.setReturnFormat(JSON)
    results = sparql.query().convert()
    results = results["results"]["bindings"]
    for res in results:
        tup = (res["label"]["value"], res["bd"]["value"], res["bpn"]["value"], res["comment"]["value"])
        athlete_list.append(tup)

    return athlete_list


def insert_athletes(athlete_tuples, cur):
    #TODO: make sure we dont have the same item twice while insert
    for tup in athlete_tuples:
        try:
            label = tup[0].encode('latin-1', 'ignore')
            name = tup[0].encode('latin-1', 'ignore').split('(')[0]
            bd = tup[1].encode('latin-1', 'ignore')
            bp = tup[2].encode('latin-1', 'ignore')
            comment = tup[3].encode('latin-1', 'ignore')
            cur.execute("INSERT INTO athlete (dbp_label, name, birth_date, birth_place, comment) VALUES (%s, %s, %s, %s, %s)",
                        (label, name, bd, bp, comment))
            con.commit()
        except Exception as e:
            traceback.print_exc(file=sys.stdout)
            con.rollback()


def insert_olympic_years(olympic_game_tuples):
    #TODO: make sure we dont have the same item twice while insert
    with con:
        cur = con.cursor()
        cur.execute("TRUNCATE TABLE olympicgame")
        con.commit()
        for tup in olympic_game_tuples:
            try:
                print tup
                cur.execute("INSERT INTO olympicgame (year, season) VALUES (%s, %s)", (tup[0], tup[1]))
                con.commit()
            except:
                con.rollback()


def query_db():
    con = mdb.connect('localhost', 'root', '', 'db_project_test')
    with con:
        cur = con.cursor()
        cur.execute("SELECT * FROM OlympicGame")
        for i in range(cur.rowcount):
            row = cur.fetchone()
            print row


def run_query():

    sparql = SPARQLWrapper("http://dbpedia.org/sparql")
    sparql.setQuery("""
    PREFIX dbpedia0: <http://dbpedia.org/ontology/>

    SELECT ?sw ?bd ?label WHERE {
    ?sw a dbpedia0:Athlete.
    ?sw dbpedia0:birthDate ?bd.
    ?sw rdfs:label ?label.
    FILTER(lang(?label) = "en")
    FILTER(datatype(?bd) = xsd:date)
    FILTER regex(?label, '^ab' , 'i')
    }
    GROUP BY ?sw
    """)
    sparql.setReturnFormat(JSON)
    results = sparql.query().convert()
    print(len(results["results"]["bindings"]))


def remove_foreign_keys():
    cur = con.cursor()
    try:
        cur.execute("ALTER TABLE AthleteGames DROP FOREIGN KEY ahtleteidconst;")
        con.commit()
    except:
        traceback.print_exc(file=sys.stdout)
        con.rollback()
    try:
        cur.execute("ALTER TABLE AthleteGames DROP FOREIGN KEY gameidconst;")
        con.commit()
    except:
        traceback.print_exc(file=sys.stdout)
        con.rollback()
    try:
        cur.execute("ALTER TABLE AthleteSportFields DROP FOREIGN KEY athleteidconst;")
        con.commit()
    except:
        traceback.print_exc(file=sys.stdout)
        con.rollback()
    try:
        cur.execute("ALTER TABLE AthleteSportFields DROP FOREIGN KEY fieldidconst;")
        con.commit()
    except:
        traceback.print_exc(file=sys.stdout)
        con.rollback()


def add_foreign_keys():
    cur = con.cursor()
    # add foreign keys for AthleteGames
    try:
        cur.execute("ALTER TABLE `AthleteGames` ADD CONSTRAINT `ahtleteidconst` FOREIGN KEY(`athlete_id`) "
                    "REFERENCES `db_project_test`. `athlete`(`athlete_id`) ON DELETE CASCADE ON UPDATE CASCADE; ")
        con.commit()
    except:
        traceback.print_exc(file=sys.stdout)
        con.rollback()
    try:
        cur.execute("ALTER TABLE `AthleteGames` ADD CONSTRAINT `gameidconst` FOREIGN KEY(`game_id`) "
                    "REFERENCES `db_project_test`. `olympicgame`(`game_id`) ON DELETE CASCADE ON UPDATE CASCADE;")
        con.commit()
    except:
        traceback.print_exc(file=sys.stdout)
        con.rollback()
        # add foreign keys for AthleteSportFields
    try:
        cur.execute("ALTER TABLE `AthleteSportFields` ADD CONSTRAINT `athleteidconst` FOREIGN KEY (`athlete_id`) "
        "REFERENCES `db_project_test`.`athlete`(`athlete_id`) ON DELETE CASCADE ON UPDATE CASCADE;")
        con.commit()
    except:
        traceback.print_exc(file=sys.stdout)
        con.rollback()
    try:
        cur.execute("ALTER TABLE `AthleteSportFields` ADD CONSTRAINT `fieldidconst` FOREIGN KEY(`field_id`) "
                   "REFERENCES `db_project_test`. `SportField`(`field_id`) ON DELETE CASCADE ON UPDATE CASCADE;")
        con.commit()
    except:
        traceback.print_exc(file=sys.stdout)
        con.rollback()

def truncate_all_dbpedia_data_tables():
    return

# remove foreign keys from tables
# remove_foreign_keys()

# truncate tables
truncate_all_dbpedia_data_tables()


# get all olympic years
# olympic_years = get_olympic_years()

# insert olympic years
# insert_olympic_years(olympic_years)

# get and insert athletes
# query_and_insert_athletes()

# restore foreign keys
# add_foreign_keys()

# get and insert athlete games and sport field
query_and_insert_athlete_field_and_games()

#close the connection
con.close()
