#!/usr/bin/python
# -*- coding: utf-8 -*-
from SPARQLWrapper import SPARQLWrapper, JSON
import MySQLdb as mdb
import sys
import traceback

sparql = SPARQLWrapper("http://dbpedia.org/sparql")
con = mdb.connect('localhost', 'root', '', 'db_project_test')

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
        cur.execute("TRUNCATE TABLE athlete")
        con.commit()
        offset = 0;
        while True:
            print offset
            if offset > 100:
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
    "limit 30 offset %s" % (offset)


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
            cur.execute("INSERT INTO Athlete (dbp_label, name, birth_date, birth_place, comment) VALUES (%s, %s, %s, %s, %s)",
                        (label, name, bd, bp, comment))
            con.commit()
        except Exception as e:
            traceback.print_exc(file=sys.stdout)
            con.rollback()


def insert_olympic_years(olympic_game_tuples):
    #TODO: make sure we dont have the same item twice while insert
    con = mdb.connect('localhost', 'root', '', 'db_project_test')
    with con:
        cur = con.cursor()
        cur.execute("TRUNCATE TABLE OlympicGame")
        con.commit()
        for tup in olympic_game_tuples:
            try:
                print tup
                cur.execute("INSERT INTO OlympicGame (year, season) VALUES (%s, %s)", (tup[0], tup[1]))
                con.commit()
            except:
                con.rollback()
    con.close()


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


# olympic_years = get_olympic_years()
# print olympic_years
# insert_olympic_years(olympic_years)
query_and_insert_athletes()

con.close()
