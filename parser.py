import csv
import mysql.connector
import timeit
import sys

'''
Processes the csv input file by formatting and extracting necessary fields.

Requirements:
Python 3.4
Python MySQL Connector (for Python 3.4): https://dev.mysql.com/downloads/connector/python/
LeagueofLegends.csv input file: https://www.kaggle.com/chuckephron/leagueoflegends/data
Note: The original input file had some erroneous rows (missing data).
The input file provided on the assignment submission is modified to correct those issues.

Options:
Set the MySQL database connection information in the CONFIG variable.
Set the DATASET_LOCATION variable with the dataset input file location.
To process a subset of the file, set the global variable PROCESS_ALL to False
and set GAMES_TO_PROCESS to the number of games that should be included in the subset.
'''

CONFIG = {'user' : 'nick',
          'password' : open("mysqlconn.txt").read().strip(),
          'host' : '127.0.0.1',
          'database' : 'project_test'}
DATASET_LOCATION = 'LeagueofLegends.csv'
PROCESS_ALL = False
GAMES_TO_PROCESS = 1


# Parses the input file and inserts the data into the provided database
def parse():
    # read in the input file
    leagueList = parseFile()
    
    # Subset the data depending on configuration settings
    if PROCESS_ALL:
        leagueDict = parseList(leagueList)
    else:
        leagueDict = parseList(leagueList[:GAMES_TO_PROCESS+1])
    databaseInserter(leagueDict)

# Reads the input file and returns a list containing each row of the input csv file
def parseFile():
    leagueList = []
    # read in the file and append each row to the leagueList
    
    with open(DATASET_LOCATION) as csvFile:
        reader = csv.reader(csvFile)
        for row in reader:
            leagueList.append(row)
            
    return leagueList

# Parses the KDA fields. Input: KDA as nested list. Output: KDA as counts for each player's stats
def parseKDA(bKDA, rKDA):
    # Initially, the KDA is represented as time, victim, killer, (list) assisters
    playerKDA = {}
    for element in bKDA:
        # check if there are any kills in the blue KDA
        if(len(element) == 0):
            continue
        
        # split name and team tag
        e1 = element[1].split(' ')
        e2 = element[2].split(' ')

        # parse the player name and increment dict value for victim/killer
        if(len(e1) > 1):
            # remove spaces from player name
            e1[1] = "".join(e1[1:])
            
            # place player in dict if first occurrence and/or increment by 1
            playerKDA[e1[1]][1] = playerKDA.setdefault(e1[1], [0,0,0])[1] + 1
            
        if(len(e2) > 1):
            # remove spaces from player name
            e2[1] = "".join(e2[1:])
            
            # place player in dict if first occurrence and/or increment by 1
            playerKDA[e2[1]][0] = playerKDA.setdefault(e2[1], [0,0,0])[0] + 1

        # parse assists
        for assister in element[3]:
            if(len(assister) == 0):
                # no assists on this kill
                continue

            # split name and team tag
            e3 = assister.split(' ')

            
            if(len(e3) > 1):
                # remove spaces from player name
                e3[1] = "".join(e3[1:])
                
                # place player in dict if first occurrence and/or increment by 1
                playerKDA[e3[1]][2] = playerKDA.setdefault(e3[1], [0,0,0])[2] + 1

    # repeat for red team
    for element in rKDA:
        # check if there are any kills in the blue KDA
        if(len(element) == 0):
            continue
        
        # split name and team tag
        e1 = element[1].split(' ')
        e2 = element[2].split(' ')

        # parse the player name and increment dict value for victim/killer
        if(len(e1) > 1):
            # remove spaces from player name
            e1[1] = "".join(e1[1:])
            
            # place player in dict if first occurrence and/or increment by 1
            playerKDA[e1[1]][1] = playerKDA.setdefault(e1[1], [0,0,0])[1] + 1
            
        if(len(e2) > 1):
            # remove spaces from player name
            e2[1] = "".join(e2[1:])
            
            # place player in dict if first occurrence and/or increment by 1
            playerKDA[e2[1]][0] = playerKDA.setdefault(e2[1], [0,0,0])[0] + 1

        # parse assists
        for assister in element[3]:
            if(len(assister) == 0):
                # no assists on this kill
                continue

            # split name and team tag
            e3 = assister.split(' ')

            
            if(len(e3) > 1):
                # remove spaces from player name
                e3[1] = "".join(e3[1:])
                
                # place player in dict if first occurrence and/or increment by 1
                playerKDA[e3[1]][2] = playerKDA.setdefault(e3[1], [0,0,0])[2] + 1

    return playerKDA

# parses list of rows into associative format
def parseList(leagueList):
    fields = leagueList[0] # field names
    
    # for each field name, store a list with each index corresponding to one game
    leagueDict = {fields[i]:[] for i in range(len(fields))}
    # additionally, store the KDA in custom format
    leagueDict['gameKDA'] = []

    # loop through the list of data, storing each subelement in the dicitonary
    for j,listElement in enumerate(leagueList[1:]):
        # KDA is given as nested list, eval to prepare it for parsing
        bKDA = eval(listElement[11])
        rKDA = eval(listElement[18])

        # parse each field for the given game
        for i, element in enumerate(listElement):
            if element[0] == '[':
                # element is in list representation, eval it
                element = eval(element)
                
                if fields[i].startswith('gold'):
                    # for gold differentials, store the ending value
                    element = element[-1]
                    
                elif fields[i] != 'blueBans' and fields[i] != 'redBans':
                    # for nested list elements other than gold diff and bans, store the count
                    element = len(element)

            # append this data value to the appropriate list
            leagueDict[fields[i]].append(element)

        # parse KDA
        kda = parseKDA(bKDA, rKDA)
        leagueDict['gameKDA'].append(kda)

    # return dictionary representation
    return leagueDict

# insert rows into the database
def dataInsert(cursor, tableName, keys, values):
    try:
        # use insert ignore to allow redundant inserts
        qry = "Insert ignore Into " + tableName + " (" + ', '.join(keys) + ") Values (%s)" % values
        qry = qry.replace('[', '')
        qry = qry.replace(']', '')
        cursor.execute(qry)
    except:
        # error in SQL query, print the query
        print("Exception encountered with query:", qry)

# format the dataset into dictionaries for each entity
def databaseInserter(leagueDict):
    conn = mysql.connector.connect(**CONFIG)

    # store relation of attribute name to dict field name
    playerFields = [('top','blueTop'),('jungle','blueJungle'),('middle','blueMiddle'),
                    ('support','blueSupport'),('ADC','blueADC'),('top','redTop'),
                    ('jungle','redJungle'),('middle','redMiddle'),
                    ('support','redSupport'),('ADC','redADC')]

    # various IDs are tracked, these are PKs of their entities to be used in linking tables
    playerIDs = {}
    tournamentIDs = {}
    teamIDs = {}
    regionNames = []

    # last ID used is tracked to determine what number to used for the next ID of each entity
    lastPlayerID = 0
    lastTournamentID = 0
    lastTeamID = 0
    lastTeamRosterID = 0
    
    for i in range(len(leagueDict['Type'])):

        # bans
        bBans = leagueDict['blueBans'][i]
        rBans = leagueDict['redBans'][i]
        blueBans = []
        redBans = []

        # blue side gets ban #1, 3, 5, 7, 9
        draftOrder = 1
        for ban in bBans:
            blueBans.append({'gameID':i+1, 'draftOrder':draftOrder, 'champion':ban})
            draftOrder += 2

        # red side gets ban #2, 4, 6, 8, 10
        draftOrder = 2
        for ban in rBans:
            redBans.append({'gameID':i+1, 'draftOrder':draftOrder, 'champion':ban})
            draftOrder += 2

        # regions
        regions = {'regionName':leagueDict['League'][i]}
        
        # tournamentDiscrim is used to uniquely identify a tournament without having access to the PK
        tournamentDiscrim = leagueDict['Season'][i]+leagueDict['Year'][i]+leagueDict['Type'][i]+leagueDict['League'][i]
        if tournamentDiscrim not in tournamentIDs:
            lastTournamentID +=1
            tournamentIDs[tournamentDiscrim] = lastTournamentID

        # tournaments
        tournaments = {'tournamentYear':leagueDict['Year'][i],
                       'season':leagueDict['Season'][i],
                       'type':leagueDict['Type'][i],
                       'tournamentID':tournamentIDs[tournamentDiscrim]}

        # regiontournamentregistration
        regionTournamentRegistration = {'regionName':leagueDict['League'][i], 'tournamentID':tournamentIDs[tournamentDiscrim]}

        redTag = leagueDict['redTeamTag'][i]
        blueTag = leagueDict['blueTeamTag'][i]

        if redTag not in teamIDs:
            lastTeamID +=1
            teamIDs[redTag] = lastTeamID
        if blueTag not in teamIDs:
            lastTeamID +=1
            teamIDs[blueTag] = lastTeamID

        redID = teamIDs[redTag]
        blueID = teamIDs[blueTag]

        # team
        rTeams = {'teamID':redID, 'teamName':redTag}
        bTeams = {'teamID':blueID, 'teamName':blueTag}

        # tournamentregistration
        redTournamentRegistration = {'tournamentID':tournamentIDs[tournamentDiscrim], 'teamID':redID}
        blueTournamentRegistration = {'tournamentID':tournamentIDs[tournamentDiscrim], 'teamID':blueID}

        # regionteamregistration
        redRegionTeamRegistration = {'regionName':leagueDict['League'][i], 'teamID':redID}
        blueRegionTeamRegistration = {'regionName':leagueDict['League'][i], 'teamID':blueID}

        if leagueDict['bResult'][i] == 1:
            winner = teamIDs[leagueDict['blueTeamTag'][i]]
        else:
            winner = teamIDs[leagueDict['redTeamTag'][i]]
            
        # game: done
        game = {'gameID':i+1, 'tournamentID':tournamentIDs[tournamentDiscrim],
                'winnerID':winner, 'length':leagueDict['gamelength'][i]}

        # playergamestats
        playerGameStats = []
        # player
        players = []

        lastTeamRosterID += 1
        rTeamRosterID = lastTeamRosterID
        lastTeamRosterID += 1
        bTeamRosterID = lastTeamRosterID
        # teamroster
        bTeamRoster = {'teamRosterID':rTeamRosterID, 'gameID':i+1, 'teamID':blueID}
        rTeamRoster = {'teamRosterID':bTeamRosterID, 'gameID':i+1, 'teamID':redID}

        rTeamStats = {'teamRosterID':rTeamRosterID, 'gameID':i+1, 'kills':0, 'deaths':0, 'assists':0}
        bTeamStats = {'teamRosterID':bTeamRosterID, 'gameID':i+1, 'kills':0, 'deaths':0, 'assists':0}

        # parse player-related data
        for dbName, dictName in playerFields:
            name = leagueDict[dictName][i]
            # remove whitespace from player name
            name = "".join(name.split())
            
            if name not in playerIDs:
                # first occurrence of this player, assign a playerID as PK
                lastPlayerID += 1
                playerIDs[name] = lastPlayerID
                
            players.append({'playerID':playerIDs[name], 'playerName':name})

            # parse team stats
            if dictName[0] == 'b':
                # currently processing blue side
                bTeamRoster[dbName] = playerIDs[name]
                
            else:
                # currently processing red side
                rTeamRoster[dbName] = playerIDs[name]
                
            try:
                playerGameStats.append({'playerID':playerIDs[name], 'gameID':i+1,'kills':leagueDict['gameKDA'][i][name][0],
                                    'deaths':leagueDict['gameKDA'][i][name][1],'assists':leagueDict['gameKDA'][i][name][2],
                                    'champion':leagueDict[dictName+'Champ'][i]})
                
                if dictName[0] == 'b':
                    # blue side team stats
                    bTeamStats['kills'] += leagueDict['gameKDA'][i][name][0]
                    bTeamStats['deaths'] += leagueDict['gameKDA'][i][name][1]
                    bTeamStats['assists'] += leagueDict['gameKDA'][i][name][2]
                    
                else:
                    # red side team stats
                    rTeamStats['kills'] += leagueDict['gameKDA'][i][name][0]
                    rTeamStats['deaths'] += leagueDict['gameKDA'][i][name][1]
                    rTeamStats['assists'] += leagueDict['gameKDA'][i][name][2]
                    
            except KeyError:
                # player has 0,0,0 KDA in this game
                playerGameStats.append({'playerID':playerIDs[name], 'gameID':i+1,'kills':0,
                                    'deaths':0,'assists':0, 'champion':leagueDict[dictName+'Champ'][i]})

        # contract: done
        blueContracts = [{'teamID':blueID, 'playerID':players[p]['playerID']} for p in range(5)]
        redContracts = [{'teamID':redID, 'playerID':players[p]['playerID']} for p in range(5,10)]


        cursor = conn.cursor()

        # insert each entity in the DB
        dataInsert(cursor, 'region', list(regions.keys()), list(regions.values()))
        dataInsert(cursor, 'tournament', list(tournaments.keys()), list(tournaments.values()))
        dataInsert(cursor, 'regiontournamentregistration', list(regionTournamentRegistration.keys()), list(regionTournamentRegistration.values()))
        dataInsert(cursor, 'team', list(bTeams.keys()), list(bTeams.values()))
        dataInsert(cursor, 'team', list(rTeams.keys()), list(rTeams.values()))
        dataInsert(cursor, 'regionTeamRegistration', list(blueRegionTeamRegistration.keys()), list(blueRegionTeamRegistration.values()))
        dataInsert(cursor, 'regionTeamRegistration', list(redRegionTeamRegistration.keys()), list(redRegionTeamRegistration.values()))
        dataInsert(cursor, 'game', list(game.keys()), list(game.values()))
        dataInsert(cursor, 'teamTournamentRegistration', list(redTournamentRegistration.keys()), list(redTournamentRegistration.values()))
        dataInsert(cursor, 'teamTournamentRegistration', list(blueTournamentRegistration.keys()), list(blueTournamentRegistration.values()))
        for z in range(10):
            dataInsert(cursor, 'player', list(players[z].keys()), list(players[z].values()))
            dataInsert(cursor, 'playerGameStats', list(playerGameStats[z].keys()), list(playerGameStats[z].values()))
        for z in range(5):
            dataInsert(cursor, 'contract', list(blueContracts[z].keys()), list(blueContracts[z].values()))
            dataInsert(cursor, 'contract', list(redContracts[z].keys()), list(redContracts[z].values()))
        dataInsert(cursor, 'teamRoster', list(rTeamRoster.keys()), list(rTeamRoster.values()))
        dataInsert(cursor, 'teamRoster', list(bTeamRoster.keys()), list(bTeamRoster.values()))
        dataInsert(cursor, 'teamGameStats', list(bTeamStats.keys()), list(bTeamStats.values()))
        dataInsert(cursor, 'teamGameStats', list(rTeamStats.keys()), list(rTeamStats.values()))
        for banList in blueBans:
            dataInsert(cursor, 'draft', list(banList.keys()), list(banList.values()))
        for banList in redBans:
            dataInsert(cursor, 'draft', list(banList.keys()), list(banList.values()))

        # commit the changes
        conn.commit()   
    conn.close()
    return 0


start = timeit.default_timer()
parse()
stop = timeit.default_timer()

print("Finished. Processing time:", stop - start)
# Running time: 60 seconds to parse and insert all data
# 7583 games
# 1590 players
# 246 teams
