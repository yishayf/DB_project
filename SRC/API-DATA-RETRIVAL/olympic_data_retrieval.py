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
# setup logging
logging.basicConfig(stream=sys.stdout, level=logging.INFO)

# globals initialization
LIMIT = 10000
SPARQL_QUERY_RETRY_COUNT = 10
TEST_MODE = False


# sparql connection setup
sparql = SPARQLWrapper("http://dbpedia.org/sparql")  # live.dbpedia is also an option...
sparql.setTimeout(300)

# MySQL connection setup
filterwarnings('ignore', category=mdb.Warning) # suppress warnings from MySQL
# con = mdb.connect('localhost', 'root', '', 'db_project_test') # local
con = mdb.connect('mysqlsrv.cs.tau.ac.il', 'DbMysql08', 'DbMysql08', 'DbMysql08') # for nova


################################################################################################################


# get olympic games info
def query_and_insert_olympic_games():
    logging.info("Getting olympic games info from DBPedia and updating our DB")
    olympic_game_tuples = get_olympic_games()
    insert_olympic_games(olympic_game_tuples)


def get_olympic_games():
    logging.info("Getting olympic games data from DBPedia")
    query_string = "PREFIX dbpedia0: <http://dbpedia.org/ontology/> " \
        "PREFIX dbpedia2: <http://dbpedia.org/property/> " \
        "SELECT ?id ?label (group_concat(?hc; separator = ', ') as ?hcn) sample(?comment) as ?comm WHERE { " \
        "?og a dbpedia0:Olympics. " \
        "?og dbpedia0:wikiPageID ?id." \
        "?og rdfs:label ?label. " \
        "optional { ?og dbpedia2:hostCity ?hc } " \
        "optional { ?og rdfs:comment ?comment }" \
        "FILTER(lang(?label) = 'en') " \
        "FILTER(lang(?comment) = 'en') " \
        "FILTER regex(?label, '^[1-2][0-9][0-9][0-9] (summer|winter) olympics' , 'i') " \
        "} " \
        "ORDER BY ?label "
    results = run_sparql_query(query_string)
    olympic_games = []
    if results:
        for res in results:
            hcn = res.get("hcn").get("value") if res.get("hcn") else ""
            comm = res.get("comm").get("value") if res.get("comm") else ""
            olympic_game_tup = (res["id"]["value"],
                                res["label"]["value"],
                                hcn,
                                comm)
            olympic_games.append(olympic_game_tup)
    return olympic_games


def insert_olympic_games(olympic_game_tuples):
    logging.info("Inserting olympic games info to our DB")
    for tup in olympic_game_tuples:
        wiki_id = tup[0]
        game_label = tup[1].encode('latin-1', 'ignore')
        host_city_text = tup[2].encode('latin-1', 'ignore')
        comment = tup[3].encode('latin-1', 'ignore')

        game_label_arr = game_label.split(' ')
        year = game_label_arr[0]
        season = game_label_arr[1]
        city = get_host_city(host_city_text)
        run_mysql_insert_query("INSERT IGNORE INTO OlympicGame (game_id, year, season) "
                        "VALUES (%s, %s, %s)",
                               (wiki_id, year, season))
        run_mysql_insert_query("UPDATE OlympicGame "
                        "set comment = %s, city = %s "
                        "WHERE game_id = %s",
                               (comment, city, wiki_id))


def get_host_city(text_from_dbp):
    split_text = text_from_dbp.split(',')
    text_parts = []
    for part in split_text:
        rspl = part.rsplit('/', 1)
        if len(rspl) == 1:
            text_parts.append(rspl[0])
        else:
            text_parts.append(rspl[1])
    city = ', '.join(text_parts)
    city = city.replace('_', ' ')
    return city


# get olympic athletes info
def query_and_insert_athletes():
    logging.info("Getting athletes info from DBPedia and updating our DB")
    offset = 0
    while True:
        if TEST_MODE and offset >= 10000:
            break
        athlete_list = get_athletes(offset)
        if athlete_list:
            offset += len(athlete_list)
            logging.info("read %d records from DBPedia" % offset)
            insert_athletes(athlete_list)
        else:
            logging.info("Done reading %d athlete records from DBPedia" % offset)
            break


def get_athletes(offset):
    logging.info("Getting athletes info from DBPedia")
    athlete_list = []
    query_offset_string = "PREFIX dbpedia0: <http://dbpedia.org/ontology/> " \
        "SELECT ?id ?label (sample(?bd) as ?bds) sample(?gamelabel) as ?gl WHERE { " \
        "?at a dbpedia0:Athlete. " \
        "?at dbpedia0:wikiPageID ?id. " \
        "?at rdfs:label ?label. " \
        "?at dbpedia0:birthDate ?bd. " \
        "?at dct:subject/rdfs:label ?gamelabel. " \
        "FILTER(lang(?label) = 'en') " \
        "FILTER(datatype(?bd) = xsd:date) " \
        "FILTER regex(?gamelabel, '^.* at the [1-2][0-9][0-9][0-9] (summer|winter) Olympics$', 'i') " \
        "} " \
        "limit %s offset %s" % (LIMIT, offset)

    results = run_sparql_query(query_offset_string)
    if results:
        for res in results:
            tup = (res["id"]["value"], res["label"]["value"], res["bds"]["value"])
            athlete_list.append(tup)
    return athlete_list


def insert_athletes(athlete_tuples):
    logging.info("Inserting athletes info to our DB")
    for tup in athlete_tuples:
        athlete_id = tup[0]
        label = tup[1].encode('latin-1', 'ignore')
        bd = tup[2].encode('latin-1', 'ignore')

        run_mysql_insert_query("INSERT IGNORE INTO Athlete (athlete_id, dbp_label) "
                        "VALUES (%s, %s)",
                               (athlete_id, label))
        run_mysql_insert_query("UPDATE Athlete "
                        "set birth_date = %s "
                        "WHERE athlete_id = %s",
                               (bd, athlete_id))


def query_and_update_athletes_comment_and_image():
    logging.info("Getting athletes short description and image url from DBPedia and updating our DB")
    offset = 0
    while True:
        if TEST_MODE and offset >= 10000:
            break
        athlete_list_with_comment = get_athletes_comment(offset)
        if athlete_list_with_comment:
            offset += len(athlete_list_with_comment)
            logging.info("read %d records from DBPedia" % offset)
            update_athletes_comment_and_image(athlete_list_with_comment)
        else:
            logging.info("Done reading %d athlete comments from DBPedia" % offset)
            break


# get athletes comments
def get_athletes_comment(offset):
    logging.info("Getting athletes short description and image url from DBPedia")
    athlete_list = []
    query_offset_string = "PREFIX dbpedia0: <http://dbpedia.org/ontology/> " \
        "SELECT ?id ?label sample(?gamelabel) as ?gl sample(?comment) as ?comm ?tn WHERE { " \
        "?at a dbpedia0:Athlete. " \
        "?at dbpedia0:wikiPageID ?id. " \
        "?at rdfs:label ?label. " \
        "?at dct:subject/rdfs:label ?gamelabel. " \
        "?at rdfs:comment ?comment. " \
        "optional { ?at dbpedia0:thumbnail ?tn }" \
        "FILTER(lang(?label) = 'en') " \
        "FILTER regex(?gamelabel, '^.* at the [1-2][0-9][0-9][0-9] (summer|winter) Olympics$', 'i') " \
        "FILTER(lang(?comment) = 'en') "   \
        "} " \
        "limit %s offset %s" % (LIMIT, offset)

    results = run_sparql_query(query_offset_string)
    if results:
        for res in results:
            comm = res.get("comm").get("value") if res.get("comm") else ""
            image_url = res.get("tn").get("value") if res.get("tn") else ""
            tup = (res["id"]["value"],
                   comm,
                   image_url)
            athlete_list.append(tup)
    return athlete_list


def update_athletes_comment_and_image(athlete_tuples):
    logging.info("Updating athletes short description and image url in our DB")
    for tup in athlete_tuples:
        athlete_id = tup[0]
        comment = tup[1].encode('latin-1', 'ignore')
        image_url = tup[2].encode('latin-1', 'ignore')
        run_mysql_insert_query("UPDATE Athlete "
                        "set comment = %s, image_url = %s"
                        "WHERE athlete_id = %s",
                               (comment, image_url, athlete_id))


# get athletes olympic sport field information
def query_and_insert_athlete_field_and_games():
    logging.info("Getting athletes fields and games info from DBPedia and updating our DB")
    offset = 0
    while True:
        if TEST_MODE and offset >= 10000:
                break
        athlete_games_list = get_athletes_games_and_field(offset)
        if athlete_games_list:
            offset += len(athlete_games_list)
            logging.info("read %d records from DBPedia" % offset)
            insert_athletes_games_and_field(athlete_games_list)
        else:
            logging.info("Done reading %d athlete sport fields records from DBPedia" % offset)
            break


def get_athletes_games_and_field(offset):
    logging.info("Getting athletes fields and games info from DBPedia")
    athlete_games_list = []
    query_offset_string = "PREFIX dbpedia0: <http://dbpedia.org/ontology/> " \
        "PREFIX dct: <http://purl.org/dc/terms/> " \
        "SELECT ?athlete_id ?gamelabel WHERE { " \
        "?sw a dbpedia0:Athlete. " \
        "?sw dbpedia0:birthDate ?bd. " \
        "?sw dbpedia0:wikiPageID ?athlete_id. " \
        "?sw dct:subject/rdfs:label ?gamelabel. " \
        "FILTER(lang(?gamelabel) = 'en') " \
        "FILTER regex(?gamelabel, '^.* at the [1-2][0-9][0-9][0-9] (summer|winter) Olympics$', 'i') " \
        "FILTER (!regex(?gamelabel, '^Medalists', 'i')) " \
        "}" \
        "limit %s offset %s" % (LIMIT, offset)
    results = run_sparql_query(query_offset_string)
    if results:
        for res in results:
            tup = (res["athlete_id"]["value"], res["gamelabel"]["value"])
            athlete_games_list.append(tup)
    return athlete_games_list


def insert_athletes_games_and_field(athlete_game_tuple):
    logging.info("Inserting athletes fields and games into our DB")
    for tup in athlete_game_tuple:
        athlete_id = tup[0]
        athlete_game = tup[1].encode('latin-1', 'ignore')
        p = re.compile('(.*) at the (\d{4}) (summer|winter) Olympics', re.IGNORECASE)
        match = p.match(athlete_game)
        if not match:
            continue
        field = match.group(1)
        year = match.group(2)
        season = match.group(3)

        # insert into sport field
        run_mysql_insert_query("INSERT IGNORE INTO OlympicSportField (field_name) VALUES (%s)",
                               [field])

        # insert into athlete column - this will fail if athlete is not found
        # field is necessarily found because we already have it from previous query
        run_mysql_insert_query("INSERT IGNORE INTO AthleteOlympicSportFields (athlete_id, field_id) "
                        "SELECT a.athlete_id, f.field_id "
                        "FROM Athlete a, OlympicSportField f "
                        "WHERE a.athlete_id = %s AND f.field_name = %s ",
                               (athlete_id, field))

        # insert into game and field columns - this will fail if athlete is not found
        run_mysql_insert_query("INSERT IGNORE INTO AthleteGames (athlete_id, game_id) "
                        "SELECT a.athlete_id, g.game_id "
                        "FROM Athlete a, OlympicGame g "
                        "WHERE a.athlete_id = %s AND g.year = %s AND g.season = %s",
                               (athlete_id, year, season))


# get athlete competitions and medals info
def query_and_insert_athlete_competitions_medals():
    logging.info("Getting athletes all competition medals info from DBPedia and updating our DB")
    medal_color = ['gold', 'silver', 'bronze']
    for color in medal_color:
        query_and_insert_athlete_competitions_medals_by_color(color)


def query_and_insert_athlete_competitions_medals_by_color(medal_color):
    logging.info("Getting athletes competition %s medals info from DBPedia and updating our DB" % medal_color)
    offset = 0
    while True:
        if TEST_MODE and offset >= 10000:
            break
        comp_list = get_competition_medalists(medal_color, offset)
        if comp_list:
            offset += len(comp_list)
            logging.info("read %d records from DBPedia" % offset)
            insert_to_competition_type_and_athlete_medals(comp_list, medal_color)
        else:
            logging.info("Done reading %d athlete competitions and %s medal records from DBPedia" %
                         (offset, medal_color))
            break


def get_competition_medalists(medal_color, offset):
    logging.info("Getting athletes competition %s medals info from DBPedia" % medal_color)
    medalists = []
    query_string = "PREFIX dbp0: <http://dbpedia.org/ontology> " \
        "SELECT ?compname ?athlete_id " \
        "WHERE { " \
        "?cn <http://dbpedia.org/ontology/%sMedalist>/dbo:wikiPageID  ?athlete_id. " \
        "?cn rdfs:label ?compname " \
        "FILTER(lang(?compname)='en') " \
        "FILTER regex(?compname, '^.* at the [1-2][0-9][0-9][0-9] (summer|winter) Olympics', 'i') " \
        "} " \
        "limit %s offset %s" % (medal_color, LIMIT, offset)
    results = run_sparql_query(query_string)
    if results:
        for res in results:
            tup = (res["athlete_id"]["value"], res["compname"]["value"])
            medalists.append(tup)
    return medalists


def insert_to_competition_type_and_athlete_medals(medals_tuples, medal_color):
    logging.info("Inserting athletes competition %s medals info into our DB" % medal_color)
    for tup in medals_tuples:
        athlete_id = tup[0]
        competition_info = tup[1].encode('latin-1', 'ignore').lower()
        p = re.compile('(.*) at the (\d{4}) (summer|winter) Olympics(  )?(.*)$', re.IGNORECASE)
        match = p.match(competition_info)
        if not match:
            continue
        comp_field = match.group(1)
        year = match.group(2)
        season = match.group(3)
        comp_name = match.group(5)
        field_and_comp = comp_field + " - " + comp_name if comp_name else comp_field

        # insert into competition type
        run_mysql_insert_query("INSERT IGNORE INTO CompetitionType (competition_name) VALUES (%s)",
                               [field_and_comp])

        # insert into athlete medals
        run_mysql_insert_query("INSERT IGNORE INTO AthleteMedals (athlete_id, game_id, competition_id, medal_color) "
                        "SELECT a.athlete_id, g.game_id, c.competition_id, %s "
                        "FROM Athlete a, OlympicGame g,  CompetitionType c "
                        "WHERE a.athlete_id = %s AND g.year = %s AND g.season = %s AND c.competition_name = %s",
                               (medal_color, athlete_id, year, season, field_and_comp))


def run_mysql_queries_lst(mysql_query_lst):
    for query in mysql_query_lst:
        run_mysql_insert_query(query)


def run_mysql_insert_query(my_sql_query, params=None):
    with con:
        cur = con.cursor()
        try:
            if params:
                cur.execute(my_sql_query, params)
            else:
                cur.execute(my_sql_query)
            con.commit()
        except:
            traceback.print_exc(file=sys.stdout)
            con.rollback()


def run_mysql_select_query(my_sql_query, params=None):
    with con:
        cur = con.cursor()
        try:
            if params:
                cur.execute(my_sql_query, params)
            else:
                cur.execute(my_sql_query)
            return cur
        except:
            traceback.print_exc(file=sys.stdout)
            con.rollback()
            return None


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


def add_two_questions_for_type():
    logging.info("Adding questions to different q_types if needed")

    for i in range(6):
        q_type = i+1
        count = check_q_count_for_q(q_type)
        num_needed = max(2-count, 0)
        add_questions_for_qtype(q_type, num_needed)


def check_q_count_for_q(q_type):
    cursor = run_mysql_select_query("SELECT count(*) FROM Question_type%d" % q_type)
    row = cursor.fetchone()
    return row[0]


def add_questions_for_qtype(q_type, num_needed):
    if q_type == 1:
        run_mysql_insert_query("INSERT INTO Question_type%d (game_id) "
                               "SELECT game_id "
                               "FROM OlympicGame "
                               "WHERE game_id NOT IN (SELECT game_id FROM Question_type%d)"
                               "AND city != ''"
                               "LIMIT %d" % (q_type, q_type, num_needed))
    elif q_type == 2:
        run_mysql_insert_query("INSERT INTO Question_type%d (athlete_id) "
                               "SELECT athlete_id "
                               "FROM Athlete "
                               "WHERE athlete_id NOT IN (SELECT athlete_id FROM Question_type%d)"
                               "LIMIT %d" % (q_type, q_type, num_needed))
    elif q_type == 3:
        run_mysql_insert_query("INSERT INTO Question_type%d (field_id) "
                               "SELECT field_id "
                               "FROM OlympicSportField "
                               "WHERE field_id NOT IN (SELECT field_id FROM Question_type%d)"
                               "LIMIT %d" % (q_type, q_type, num_needed))
    elif q_type == 4:
        run_mysql_insert_query("INSERT INTO Question_type%d (athlete_id, medal_color) "
                               "SELECT a.athlete_id, colors.medal_color "
                               "FROM Athlete a, (SELECT DISTINCT medal_color FROM AthleteMedals) as colors "
                               "                WHERE concat(colors.medal_color, a.athlete_id) NOT IN "
                               "(SELECT concat(medal_color, athlete_id) FROM Question_type%d) "
                               "LIMIT %d" % (q_type, q_type, num_needed))
    elif q_type == 5:
        run_mysql_insert_query("INSERT INTO Question_type%d (game_id) "
                               "SELECT game_id "
                               "FROM OlympicGame "
                               "WHERE game_id NOT IN (SELECT game_id FROM Question_type%d)"
                               "LIMIT %d" % (q_type, q_type, num_needed))
    elif q_type == 6:
        run_mysql_insert_query("INSERT INTO Question_type%d (athlete_id) "
                               "SELECT DISTINCT athlete_id "
                               "FROM AthleteMedals "
                               "WHERE athlete_id NOT IN (SELECT athlete_id FROM Question_type%d)"
                               "LIMIT %d" % (q_type, q_type, num_needed))


def main():
    logging.info("Started database update from DBPedia")

    # get all olympic years and insert to db
    query_and_insert_olympic_games()

    # get and insert athletes
    query_and_insert_athletes()

    # get and insert athlete comment
    query_and_update_athletes_comment_and_image()

    # get and insert athlete games and sport field
    query_and_insert_athlete_field_and_games()

    # get and insert athlete medals and their competitions
    query_and_insert_athlete_competitions_medals()

    # add two new questions for every question type if there aren't any yet
    add_two_questions_for_type()

    logging.info("Done!")

# run main function
main()

# close the connection to MySQL
con.close()
