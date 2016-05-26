#!/usr/bin/python
# -*- coding: utf-8 -*-
from SPARQLWrapper import SPARQLWrapper, JSON  # on nova - need to run: pip install --user SPARQLWrapper
import MySQLdb as mdb
import sys
import traceback
import re
import logging
from warnings import filterwarnings


###############################################################################################################
# globals initialization
LIMIT = 10000
SPARQL_QUERY_RETRY_COUNT = 5

# sparql connection setup
sparql = SPARQLWrapper("http://dbpedia.org/sparql") # live.dbpedia is also an option...
sparql.setTimeout(300)

# MySQL connection setup
filterwarnings('ignore', category=mdb.Warning) # supress warnings from MySQL
con = mdb.connect('localhost', 'root', '', 'db_project_test') # unix_socket = '/opt/lampp/var/mysql/mysql.sock')
# con = mdb.connect('mysqlsrv.cs.tau.ac.il', 'DbMysql08', 'DbMysql08', 'DbMysql08') # for nova

################################################################################################################


def query_and_insert_athlete_competiotions_medals():
    logging.info("Getting athletes all competition medals info from DBPedia and updating our DB")
    medal_color = ['gold', 'silver', 'bronze']
    for color in medal_color:
        query_and_insert_athlete_competiotions_medals_by_color(color)


def query_and_insert_athlete_competiotions_medals_by_color(medal_color):
    logging.info("Getting athletes competition %s medals info from DBPedia and updating our DB" % medal_color)
    with con:
        offset = 0
        while True:
            logging.info("read %d records from DBPedia" % offset)
            # if offset > 10000:  # todo: remove this
            #     break
            comp_list = get_competition_medalists(medal_color, offset)
            if comp_list:
                offset += len(comp_list)
                insert_to_competition_type_and_athletemedals(comp_list, medal_color, con)
            else:
                break


def get_competition_medalists(medal_color, offset):
    logging.info("Getting athletes competition %s medals info from DBPedia" % medal_color)
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
    # sparql.setQuery(query_string)
    #
    # sparql.setReturnFormat(JSON)
    # results = sparql.query().convert()=
    results = run_sparql_query(query_string)
    for res in results:
        tup = (res["compname"]["value"], res["personlabel"]["value"])
        medalists.append(tup)
    return medalists


def insert_to_competition_type_and_athletemedals(medals_tuples, medal_color, con):
    logging.info("Inserting athletes competition %s medals info into our DB" % medal_color)
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
    logging.info("Getting athletes fields and games info from DBPedia and updating our DB")
    with con:
        offset = 0
        while True:
            logging.info("read %d records from DBPedia" % offset)
            # if offset > 10000: # todo: remove this
            #     break
            athlete_games_list = get_athletes_games_and_field(offset)
            if athlete_games_list:
                offset += len(athlete_games_list)
                insert_athletes_games_and_field(athlete_games_list, con)
            else:
                break


def get_athletes_games_and_field(offset):
    logging.info("Getting athletes fields and games info from DBPedia")
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
    results = run_sparql_query(query_offset_string)
    for res in results:
        tup = (res["personlabel"]["value"], res["gamelabel"]["value"])
        athlete_games_list.append(tup)
    return athlete_games_list


def insert_athletes_games_and_field(athlete_game_tuple, con):
    logging.info("Inserting athletes fields and games into our DB")
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
    logging.info("Getting olympic games info from DBPedia and updating our DB")
    olympic_game_tuples = get_olympic_games()
    insert_olympic_games(olympic_game_tuples)


def get_olympic_games():
    logging.info("Getting olympic games data from DBPedia")
    query_string = "PREFIX dbpedia0: <http://dbpedia.org/ontology/> " \
        "PREFIX  dbpedia2: <http://dbpedia.org/property/> " \
        "SELECT ?label (group_concat(?hc; separator = ', ') as ?hcn) WHERE { " \
        "?og a dbpedia0:Olympics. " \
        "?og rdfs:label ?label. " \
        "optional { ?og dbpedia2:hostCity ?hc } " \
        "FILTER(lang(?label) = 'en') " \
        "FILTER regex(?label, '^[1-2][0-9][0-9][0-9] (summer|winter) olympics' , 'i') " \
        "} " \
        "ORDER BY ?label "
    results = run_sparql_query(query_string)
    olympic_games = []

    for res in results:
        olympic_game_tup = (res["label"]["value"], res["hcn"]["value"])
        olympic_games.append(olympic_game_tup)
    return olympic_games


def get_host_city(text_from_dbp):
    splitted_text = text_from_dbp.split(',')
    text_parts = []
    for part in splitted_text:
        rspl = part.rsplit('/', 1)
        if len(rspl) == 1:
            text_parts.append(rspl[0])
        else:
            text_parts.append(rspl[1])
    return ', '.join(text_parts)


def insert_olympic_games(olympic_game_tuples):
    logging.info("Inserting olympic games info to our DB")
    with con:
        cur = con.cursor()
        for tup in olympic_game_tuples:
            game_label = tup[0].encode('latin-1', 'ignore')
            host_city_text = tup[1].encode('latin-1', 'ignore')
            game_label_arr = game_label.split(' ')
            year = game_label_arr[0]
            season = game_label_arr[1]
            city = get_host_city(host_city_text)
            try:
                cur.execute("INSERT INTO OlympicGame (year, season, city) VALUES (%s, %s, %s)", (year, season, city))
                con.commit()
            except:
                con.rollback()


def query_and_insert_athletes():
    # TODO: don't enter athlete with too many words in label (e.g. Los Angeles Dodgers minor league players)
    logging.info("Getting athletes info from DBPedia and updating our DB")
    with con:
        offset = 0;
        while True:
            logging.info("read %d records from DBPedia" % offset)
            # if offset > 10000:
            #     break
            athlete_list = get_athletes(offset)
            if athlete_list:
                offset += len(athlete_list)
                insert_athletes(athlete_list, con)
            else:
                break


def get_athletes(offset):
    logging.info("Getting athletes info from DBPedia")
    athlete_list = []
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

    # sparql.setQuery(query_offset_string)
    # sparql.setReturnFormat(JSON)
    # results = sparql.query().convert()
    results = run_sparql_query(query_offset_string)#results["results"]["bindings"]
    for res in results:
        tup = (res["label"]["value"], res["bds"]["value"])
        athlete_list.append(tup)

    return athlete_list


def insert_athletes(athlete_tuples, con):
    logging.info("Inserting athletes info to our DB")
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


def query_and_update_athletes_birth_place():
    logging.info("Getting athletes birth place from DBPedia and updating our DB")
    with con:
        offset = 0;
        while True:
            logging.info("read %d records from DBPedia" % offset)
            # if offset > 10000:
            #     break
            athlete_list_with_bp = get_athletes_birth_place(offset)
            if athlete_list_with_bp:
                offset += len(athlete_list_with_bp)
                update_athletes_birth_place(athlete_list_with_bp, con)
            else:
                break


def get_athletes_birth_place(offset):
    logging.info("getting athletes birth place from DBPedia")

    athlete_list = []
    query_offset_string = "PREFIX dbpedia0: <http://dbpedia.org/ontology/> " \
        "SELECT ?label sample(?gamelabel) as ?gl (group_concat(?bp; separator = ', ') as ?bpn) WHERE { " \
        "?at a dbpedia0:Athlete. " \
        "?at rdfs:label ?label. " \
        "?at dct:subject/rdfs:label ?gamelabel. " \
        "?at dbpedia0:birthPlace/rdfs:label ?bp. " \
        "FILTER(lang(?label) = 'en') " \
        "FILTER regex(?gamelabel, '^.* at the .* (summer|winter) Olympics$', 'i') " \
        "FILTER(lang(?bp) = 'en') " \
        "} " \
        "limit %s offset %s" % (LIMIT, offset)

    # sparql.setQuery(query_offset_string)
    # sparql.setReturnFormat(JSON)
    # results = sparql.query().convert()
    results = run_sparql_query(query_offset_string)#results["results"]["bindings"]
    for res in results:
        tup = (res["label"]["value"], res["bpn"]["value"])
        athlete_list.append(tup)
    return athlete_list


def update_athletes_birth_place(athlete_tuples, con):
    logging.info("Updating athletes birth place in our DB")
    for tup in athlete_tuples:
        cur = con.cursor()
        label = tup[0].encode('latin-1', 'ignore')
        bp = tup[1].encode('latin-1', 'ignore')
        try:
            cur.execute("UPDATE Athlete "
                        "set birth_place = %s "
                        "WHERE dbp_label = %s",
                        (bp, label))
            con.commit()
        except Exception as e:
            traceback.print_exc(file=sys.stdout)
            con.rollback()


def query_and_update_athletes_comment():
    logging.info("Getting athletes short description from DBPedia and updating our DB")
    with con:
        offset = 0;
        while True:
            print offset
            # if offset > 10000:
            #     break
            athlete_list_with_comment = get_athletes_comment(offset)
            if athlete_list_with_comment:
                offset += len(athlete_list_with_comment)
                update_athletes_comment(athlete_list_with_comment, con)
            else:
                break


def get_athletes_comment(offset):
    logging.info("Getting athletes short description from DBPedia")
    athlete_list = []
    query_offset_string = "PREFIX dbpedia0: <http://dbpedia.org/ontology/> " \
        "SELECT ?label sample(?gamelabel) as ?gl sample(?comment) as ?comm WHERE { " \
        "?at a dbpedia0:Athlete. " \
        "?at rdfs:label ?label. " \
        "?at dct:subject/rdfs:label ?gamelabel. " \
        "?at rdfs:comment ?comment." \
        "FILTER(lang(?label) = 'en') " \
        "FILTER regex(?gamelabel, '^.* at the [1-2][0-9][0-9][0-9] (summer|winter) Olympics$', 'i') " \
        "FILTER(lang(?comment) = 'en') "   \
        "} " \
        "limit %s offset %s" % (LIMIT, offset)

    # sparql.setQuery(query_offset_string)
    # sparql.setReturnFormat(JSON)
    # results = sparql.query().convert()
    results = run_sparql_query(query_offset_string) #results["results"]["bindings"]
    for res in results:
        tup = (res["label"]["value"], res["comm"]["value"])
        athlete_list.append(tup)
    return athlete_list


def update_athletes_comment(athlete_tuples, con):
    logging.info("Updating athletes short description in our DB")
    for tup in athlete_tuples:
        cur = con.cursor()
        label = tup[0].encode('latin-1', 'ignore')
        comment = tup[1].encode('latin-1', 'ignore')
        try:
            cur.execute("UPDATE Athlete "
                        "set comment = %s "
                        "WHERE dbp_label = %s",
                        (comment, label))
            con.commit()
        except Exception as e:
            traceback.print_exc(file=sys.stdout)
            con.rollback()


def remove_foreign_keys():
    logging.info("Removing Foreign keys before table trancation")
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
    logging.info("Adding foreign keys")
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
    logging.info("Truncation all tables containing data from DBPedia")
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


def run_sparql_query(query):
    for i in range(SPARQL_QUERY_RETRY_COUNT):
        try:
            sparql.setQuery(query)
            sparql.setReturnFormat(JSON)
            results = sparql.query().convert()
            results = results["results"]["bindings"]
            return results
        except:
            logging.warning("sparql query failed, retrying")
            traceback.print_exc(file=sys.stdout)
            continue
    # if we got here there are no more reties
    logging.error("sparql query failed, no more retries!\n"
                  "The Query was:\n%s" % query)


def main():
    logging.info("Started database update from DBPedia")
    # remove foreign keys from tables
    remove_foreign_keys()

    # truncate tables
    truncate_all_dbpedia_data_tables()

    # restore foreign keys
    add_foreign_keys()

    # get all olympic years and insert to db
    query_and_insert_olympic_games()  # todo: add comments for olympic game

    # get and insert athletes
    query_and_insert_athletes()

    # get and insert athlete birth place # TODO: do we need this?
    ####query_and_update_athletes_birth_place()

    # get and insert athlete comment
    query_and_update_athletes_comment()

    # get and insert athlete games and sport field
    query_and_insert_athlete_field_and_games()

    # get and insert athlete medals and their competitions
    query_and_insert_athlete_competiotions_medals()

    logging.info("Done!")


# setup logging
logging.basicConfig(level=logging.INFO)

# run main function
main()

# close the connection to MySQL
con.close()
