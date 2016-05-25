#!/usr/bin/python
# -*- coding: utf-8 -*-
from SPARQLWrapper import SPARQLWrapper, JSON
import MySQLdb as mdb
import sys
import traceback
import re

LIMIT = 10000

sparql = SPARQLWrapper("http://dbpedia.org/sparql")
con = mdb.connect('localhost', 'root', '', 'db_project_test', unix_socket = '/opt/lampp/var/mysql/mysql.sock')


def query_and_insert_athlete_competiotions_medals():
    medal_color = ['gold', 'silver', 'bronze']
    for color in medal_color:
        query_and_insert_athlete_competiotions_medals_by_color(color)


def query_and_insert_athlete_competiotions_medals_by_color(medal_color):
    with con:
        offset = 0
        while True:
            print offset
            # if offset > 10000:  # todo: remove this
            #     break
            comp_list = get_competition_medalists(medal_color, offset)
            if comp_list:
                offset += len(comp_list)
                insert_to_competition_type_and_athletemedals(comp_list, medal_color, con)
            else:
                break


def get_competition_medalists(medal_color, offset):
    medalists = []
    query_string = "PREFIX dbp0: <http://dbpedia.org/ontology> " \
    "SELECT ?compname ?personlabel " \
    "WHERE { " \
    "?cn <http://dbpedia.org/ontology/%sMedalist>/rdfs:label ?personlabel. " \
    "?cn rdfs:label ?compname " \
    "FILTER(lang(?personlabel)='en') " \
    "FILTER(lang(?compname)='en') " \
    "FILTER regex(?compname, '^.* at the [1-2][0-9][0-9][0-9] (summer|winter) Olympics', 'i') " \
    "} " \
    "limit %s offset %s"  % (medal_color, LIMIT, offset)
    sparql.setQuery(query_string)

    sparql.setReturnFormat(JSON)
    results = sparql.query().convert()

    results = results["results"]["bindings"]
    for res in results:
        tup = (res["compname"]["value"], res["personlabel"]["value"])
        medalists.append(tup)
    return medalists


def insert_to_competition_type_and_athletemedals(medals_tuples, medal_color, con):
    #TODO: make sure we dont have the same item twice while insert - it's ok beacuse we have primary key
    cur = con.cursor()
    for tup in medals_tuples:
        try:
            competition_info = tup[0].encode('latin-1', 'ignore').lower()
            athlete_label = tup[1].encode('latin-1', 'ignore')
            p = re.compile('(.*) at the (\d{4}) (summer|winter) Olympics(  )?(.*)$', re.IGNORECASE)
            match = p.match(competition_info)

            comp_field = match.group(1)
            year = match.group(2)
            season = match.group(3)
            comp_name = match.group(5)
            field_and_comp =  comp_field + "_" + comp_name if comp_name else comp_field

            # insert into competition type
            cur.execute("INSERT IGNORE INTO CompetitionType (competition_name) VALUES (%s)", [field_and_comp])
            con.commit()

            # insert into athlete medals
            cur.execute("INSERT IGNORE INTO AthleteMedals (athlete_id, game_id, competition_id, medal_color) "
                        "SELECT a.athlete_id, g.game_id, c.competition_id, %s "
                        "FROM Athlete a, OlympicGame g,  CompetitionType c "
                        "WHERE a.dbp_label = %s AND g.year = %s AND g.season = %s AND c.competition_name = %s",
                        (medal_color, athlete_label, year, season, field_and_comp))
            con.commit()
        except Exception as e:
            traceback.print_exc(file=sys.stdout)
            con.rollback()


def query_and_insert_athlete_field_and_games():
    with con:
        offset = 0
        while True:
            print offset
            # if offset > 10000: # todo: remove this
            #     break
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
        "FILTER (!regex(?gamelabel, '^Medalists', 'i')) " \
        "}" \
        "limit %s offset %s" % (LIMIT, offset)

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
            cur.execute("INSERT IGNORE INTO OlympicSportField (field_name) VALUES (%s)", [field])
            con.commit()

            # insert into athlete column - this will fail if athlete is not found
            # field is necessarily found because we already have it from previuos query
            cur.execute("INSERT IGNORE INTO AthleteOlympicSportFields (athlete_id, field_id) "
                        "SELECT a.athlete_id, f.field_id "
                        "FROM Athlete a, OlympicSportField f "
                        "WHERE a.dbp_label = %s AND f.field_name = %s ",
                        (athlete_label, field))
            con.commit()

            # insert into game and field columns - this will fail if athlete is not found
            cur.execute("INSERT IGNORE INTO AthleteGames (athlete_id, game_id, field_id) "
                        "SELECT a.athlete_id, g.game_id, f.field_id "
                        "FROM Athlete a, OlympicGame g, OlympicSportField f "
                        "WHERE a.dbp_label = %s AND g.year = %s AND g.season = %s AND f.field_name = %s",
                        (athlete_label, year, season, field))
            con.commit()

        except:
            traceback.print_exc(file=sys.stdout)
            con.rollback()


def query_and_insert_olympic_games():
    # todo: make sure we update the cities
    olympic_game_tuples = get_olympic_games()
    insert_olympic_games(olympic_game_tuples)


def get_olympic_games():
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


def insert_olympic_games(olympic_game_tuples):
    with con:
        cur = con.cursor()
        for tup in olympic_game_tuples:
            try:
                cur.execute("INSERT INTO OlympicGame (year, season) VALUES (%s, %s)", (tup[0], tup[1]))
                con.commit()
            except:
                con.rollback()


def query_and_insert_athletes():
    # TODO: don't enter athlete qith too many words in label (e.g. Los Angeles Dodgers minor league players)
    with con:
        offset = 0;
        while True:
            print offset
            # if offset > 10000:
            #     break
            athlete_list = get_athletes(offset)
            if athlete_list:
                offset += len(athlete_list)
                insert_athletes(athlete_list, con)
            else:
                break


def get_athletes(offset):
    athlete_list = []
    #todo: we get more than one bd - maybe add sample!
    # query_offset_string = "PREFIX dbpedia0: <http://dbpedia.org/ontology/> " \
    #     "SELECT ?label sample(?bd) as ?bds sample(?gamelabel) as ?gl ?comment WHERE { " \
    #     "?at a dbpedia0:Athlete. " \
    #     "?at rdfs:label ?label. " \
    #     "?at dbpedia0:birthPlace/rdfs:label ?bp. " \
    #     "?at rdfs:comment ?comment " \
    #     "?at dct:subject/rdfs:label ?gamelabel. " \
    #     "FILTER(lang(?label) = 'en') " \
    #     "FILTER(datatype(?bd) = xsd:date) " \
    #     "FILTER(lang(?bp) = 'en')" \
    #     "FILTER(lang(?comment) = 'en') " \
    #     "FILTER regex(?gamelabel, '^.* at the [1-2][0-9][0-9][0-9] (summer|winter) Olympics$', 'i') " \
    #     "} " \
    #     "limit %s offset %s" % (LIMIT, offset)

    # query_offset_string = "PREFIX dbpedia0: <http://dbpedia.org/ontology/> " \
    #     "SELECT ?label ?comment (sample(?bd) as ?bds) sample(?gamelabel) as ?gl (group_concat(?bp; separator = ', ') as ?bpn) WHERE { " \
    #     "?at a dbpedia0:Athlete. " \
    #     "?at rdfs:label ?label. " \
    #     "?at rdfs:comment ?comment. " \
    #     "?at dbpedia0:birthDate ?bd. " \
    #     "?at dct:subject/rdfs:label ?gamelabel. " \
    #     "?at dbpedia0:birthPlace/rdfs:label ?bp. " \
    #     "FILTER(lang(?label) = 'en') " \
    #     "FILTER(datatype(?bd) = xsd:date) " \
    #     "FILTER(lang(?bp) = 'en')" \
    #     "FILTER(lang(?comment) = 'en') " \
    #     "FILTER regex(?gamelabel, '^.* at the [1-2][0-9][0-9][0-9] (summer|winter) Olympics$', 'i') " \
    #     "} " \
    #     "limit %s offset %s" % (LIMIT, offset)

    query_offset_string = "PREFIX dbpedia0: <http://dbpedia.org/ontology/> " \
        "SELECT ?label (sample(?bd) as ?bds) sample(?gamelabel) as ?gl WHERE { " \
        "?at a dbpedia0:Athlete. " \
        "?at rdfs:label ?label. " \
        "?at dbpedia0:birthDate ?bd. " \
        "?at dct:subject/rdfs:label ?gamelabel. " \
        "FILTER(lang(?label) = 'en') " \
        "FILTER(datatype(?bd) = xsd:date) " \
        "FILTER regex(?gamelabel, '^.* at the [1-2][0-9][0-9][0-9] (summer|winter) Olympics$', 'i') " \
        "} " \
        "limit %s offset %s" % (LIMIT, offset)

    sparql.setQuery(query_offset_string)

    sparql.setReturnFormat(JSON)
    results = sparql.query().convert()
    results = results["results"]["bindings"]
    for res in results:
        tup = (res["label"]["value"], res["bds"]["value"])
        athlete_list.append(tup)

    return athlete_list


def insert_athletes(athlete_tuples, con):
    #TODO: make sure we dont have the same item twice while insert
    for tup in athlete_tuples:
        cur = con.cursor()
        label = tup[0].encode('latin-1', 'ignore')
        name = tup[0].encode('latin-1', 'ignore').split('(')[0]
        bd = tup[1].encode('latin-1', 'ignore')
        # bp = tup[2].encode('latin-1', 'ignore')
        # comment = tup[3].encode('latin-1', 'ignore')
        try:
            cur.execute("INSERT INTO Athlete (dbp_label, name, birth_date) "
                        "VALUES (%s, %s, %s)",
                        (label, name, bd))
            con.commit()
        except Exception as e:
            traceback.print_exc(file=sys.stdout)
            con.rollback()


# def query_db():
#     con = mdb.connect('localhost', 'root', '', 'db_project_test')
#     with con:
#         cur = con.cursor()
#         cur.execute("SELECT * FROM OlympicGame")
#         for i in range(cur.rowcount):
#             row = cur.fetchone()
#             print row
#
#
# def run_query():
#     sparql = SPARQLWrapper("http://dbpedia.org/sparql")
#     sparql.setQuery("""
#     PREFIX dbpedia0: <http://dbpedia.org/ontology/>
#
#     SELECT ?sw ?bd ?label WHERE {
#     ?sw a dbpedia0:Athlete.
#     ?sw dbpedia0:birthDate ?bd.
#     ?sw rdfs:label ?label.
#     FILTER(lang(?label) = "en")
#     FILTER(datatype(?bd) = xsd:date)
#     FILTER regex(?label, '^ab' , 'i')
#     }
#     GROUP BY ?sw
#     """)
#     sparql.setReturnFormat(JSON)
#     results = sparql.query().convert()
#     print(len(results["results"]["bindings"]))


def remove_foreign_keys():
    drop_queries = [
        "ALTER TABLE AthleteGames DROP FOREIGN KEY ahtleteidconst;",
        "ALTER TABLE AthleteGames DROP FOREIGN KEY gameidconst;",
        "ALTER TABLE AthleteGames DROP FOREIGN KEY fieldidconst1;",
        "ALTER TABLE AthleteOlympicSportFields DROP FOREIGN KEY athleteidconst;",
        "ALTER TABLE AthleteOlympicSportFields DROP FOREIGN KEY fieldidconst;"
        "ALTER TABLE AthleteMedals DROP FOREIGN KEY athleteidconst2;",
        "ALTER TABLE AthleteMedals DROP FOREIGN KEY compidconst;",
        "ALTER TABLE AthleteMedals DROP FOREIGN KEY gameidconst1;"
    ]
    run_mysql_queries_lst(drop_queries)


def add_foreign_keys():
    # add foreign keys for the needed tables
    foreign_keys_add_queries = [
        "ALTER TABLE `AthleteGames` ADD CONSTRAINT `ahtleteidconst` FOREIGN KEY(`athlete_id`) "
        "REFERENCES `db_project_test`. `Athlete`(`athlete_id`) ON DELETE CASCADE ON UPDATE CASCADE; ",
        "ALTER TABLE `AthleteGames` ADD CONSTRAINT `gameidconst` FOREIGN KEY(`game_id`) "
        "REFERENCES `db_project_test`. `OlympicGame`(`game_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
        "ALTER TABLE `AthleteGames` ADD CONSTRAINT `fieldidconst1` FOREIGN KEY (`field_id`) "
        "REFERENCES `db_project_test`.`OlympicSportField`(`field_id`) ON DELETE RESTRICT ON UPDATE RESTRICT;",
        "ALTER TABLE `AthleteOlympicSportFields` ADD CONSTRAINT `athleteidconst` FOREIGN KEY (`athlete_id`) "
        "REFERENCES `db_project_test`.`Athlete`(`athlete_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
        "ALTER TABLE `AthleteOlympicSportFields` ADD CONSTRAINT `fieldidconst` FOREIGN KEY(`field_id`) "
        "REFERENCES `db_project_test`. `OlympicSportField`(`field_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
        "ALTER TABLE `AthleteMedals` ADD CONSTRAINT `athleteidconst2` FOREIGN KEY (`athlete_id`) "
        "REFERENCES `db_project_test`.`Athlete`(`athlete_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
        "ALTER TABLE `AthleteMedals` ADD CONSTRAINT `gameidconst1` FOREIGN KEY (`game_id`) "
        "REFERENCES `db_project_test`.`OlympicGame`(`game_id`) ON DELETE CASCADE ON UPDATE CASCADE;",
        "ALTER TABLE `AthleteMedals` ADD CONSTRAINT `compidconst` FOREIGN KEY (`competition_id`) "
        "REFERENCES `db_project_test`.`CompetitionType`(`competition_id`) ON DELETE CASCADE ON UPDATE CASCADE;"
    ]
    run_mysql_queries_lst(foreign_keys_add_queries)


def truncate_all_dbpedia_data_tables():
    queries_lst = [
        "TRUNCATE TABLE OlympicGame",
        "TRUNCATE TABLE Athlete;",
        "TRUNCATE TABLE OlympicSportField",
        "TRUNCATE TABLE AthleteOlympicSportFields",
        "TRUNCATE TABLE AthleteGames",
        "TRUNCATE TABLE CompetitionType",
        "TRUNCATE TABLE AthleteMedals"
    ]
    run_mysql_queries_lst(queries_lst)


def run_mysql_queries_lst(mysql_query_lst):
    for query in mysql_query_lst:
        run_mysql_query(query)


def run_mysql_query(my_sql_query):
    with con:
        cur = con.cursor()
        try:
            cur.execute(my_sql_query)
            con.commit()
        except:
            traceback.print_exc(file=sys.stdout)
            con.rollback()


# # remove foreign keys from tables
# remove_foreign_keys()
#
# # truncate tables
# truncate_all_dbpedia_data_tables()
#
# # restore foreign keys
# add_foreign_keys()
#
# # get all olympic years and insert to db
# query_and_insert_olympic_games()

# get and insert athletes
query_and_insert_athletes()
# TODO: add function to update birth place and comment
# # get and insert athlete games and sport field
# query_and_insert_athlete_field_and_games()
#
# # get and insert athlete medals and their competitions
# query_and_insert_athlete_competiotions_medals()
#
# #close the connection
# con.close()
