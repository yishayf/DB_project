#!/usr/bin/python
# -*- coding: utf-8 -*-
from SPARQLWrapper import SPARQLWrapper, JSON
import MySQLdb as mdb

sparql = SPARQLWrapper("http://dbpedia.org/sparql")

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


olympic_years = get_olympic_years()
print olympic_years
insert_olympic_years(olympic_years)
