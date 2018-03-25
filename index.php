<!doctype html>
<html>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script type="text/javascript" src="http://static.fusioncharts.com/code/latest/fusioncharts.js"></script>
	<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <a class="navbar-brand" href="index.php">League of Legends eSports Database</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item active">
        <a class="nav-link" href="index.php">Home<span class="sr-only">(current)</span></a>
      </li>
      <li class="nav-item">
        <form action="index.php" method="post">
				<input type="hidden" name="status" value="stats">
					<a class="nav-link" href="javascript:;" onclick="parentNode.submit();">Stats</a>
			</form>
      </li>
			<li class="nav-item">
        <form action="index.php" method="post" id="query1">
				<input type="hidden" name="status" value="results">
				<input type="hidden" name="query" value="select champion as Champion, count(champion) as &quot;# Bans&quot; from draft group by champion having count(champion) = 
				(select max(counted) from (select champion, count(champion) as counted from draft group by champion) as counts);">
				<a class="nav-link" href="javascript:;" onclick="parentNode.submit();">Query 1</a>
			</form>
      </li>
						<li class="nav-item">
        <form action="index.php" method="post" id="query2">
				<input type="hidden" name="status" value="results">
				<input type="hidden" name="query" value="select playerName as &quot;Player&quot;, player.playerID as &quot;ID&quot; from player, playerGameStats, game, 
					tournament where player.playerID = playerGameStats.playerID and
					playerGameStats.gameID = game.gameID and game.tournamentID =
					tournament.tournamentID and (tournament.season = &quot;Summer&quot; or 
					tournament.season = &quot;Spring&quot;) and tournament.type = &quot;Playoffs&quot;
					and tournament.tournamentYear = 2015
					group by player.playerID having count(season) > 1
					limit 100000;">
				<a class="nav-link" href="javascript:;" onclick="parentNode.submit();">Query 2</a>
			</form>
      </li>
						<li class="nav-item">
        <form action="index.php" method="post" id="query3">
				<input type="hidden" name="status" value="results">
				<input type="hidden" name="query" value="select playerName as &quot;Player&quot;, number_of_tournaments as &quot;# Tournaments&quot; from (select playerName, player.playerID, count(distinct tournament.tournamentID) as number_of_tournaments
					from player, playerGameStats, game, tournament
					where player.playerID = playerGameStats.playerID and
					playerGameStats.gameID = game.gameID and 
					game.tournamentID = tournament.tournamentID
					group by playerID order by number_of_tournaments desc limit 1) as countedTournaments
					limit 100000;">
				<a class="nav-link" href="javascript:;" onclick="parentNode.submit();">Query 3</a>
			</form>
      </li>
						<li class="nav-item">
        <form action="index.php" method="post" id="query4">
				<input type="hidden" name="status" value="results">
				<input type="hidden" name="query" value="select teamName as Team, games_played as &quot;# Games&quot; from (select teamName, count(teamName) as games_played
					from team 
					join teamRoster on team.teamID = teamRoster.teamID 
					join game on teamRoster.gameID = game.gameID
					group by team.teamID
					having games_played > 100) as countGames
					order by games_played desc
					limit 100000;">
					<a class="nav-link" href="javascript:;" onclick="parentNode.submit();">Query 4</a>
			</form>
      </li>
						<li class="nav-item">
        <form action="index.php" method="post" id="query5">
				<input type="hidden" name="status" value="results">
				<input type="hidden" name="query" value="select distinct team.teamName, tournament.* from tournament join teamTournamentRegistration on (tournament.tournamentID = teamTournamentRegistration.tournamentID) and
					tournament.tournamentYear = 2017 and tournament.type = &quot;Playoffs&quot;
					right join team on (teamTournamentRegistration.teamID = team.teamID)
					join regionTeamRegistration on team.teamID = regionTeamRegistration.teamID
					join region on regionTeamRegistration.regionName = region.regionName and  region.regionName = &quot;NALCS&quot
					;">
				<a class="nav-link" href="javascript:;" onclick="parentNode.submit();">Query 5</a>
			</form>
      </li>
  </div>
</nav>
<?php
include("fusioncharts.php");
$servername = "twackycats.com";
$username = "testuser";
$password = "***********";
$dbname = "project";

$conn = mysqli_connect($servername, $username, $password, $dbname);

$status = "home";
$status = $_POST["status"];
$submit = $_POST["submitted"];
$qry = $_POST["query"];
if($submit == "Execute Query"){
	$status = "adhoc";
}

switch($status){
		case "stats":
		$qry = "select region.regionName as tournamentName, avg(length) as gameLength from region, tournament, game, regionTournamentRegistration
		where region.regionName = regionTournamentRegistration.regionName
		and tournament.tournamentID = regionTournamentRegistration.tournamentID
		and tournament.tournamentID = game.tournamentID
		group by region.regionName;";
		$result = mysqli_query($conn, $qry);
		  if ($result) {
    // The `$arrData` array holds the chart attributes and data
    $arrData = array(
      "chart" => array(
          "caption" => "Average Game Length by Tournament",
					"xaxisname" => "Tournament",
					"yaxisname" => "Average Game Length",
					"labeldisplay" => "rotate",
					"slantlabels" => "1",
					"setAdaptiveYMin" => "1",
          "paletteColors" => "#0075c2",
          "bgColor" => "#ffffff",
          "borderAlpha"=> "20",
          "canvasBorderAlpha"=> "0",
          "usePlotGradientColor"=> "0",
          "plotBorderAlpha"=> "10",
          "showXAxisLine"=> "1",
          "xAxisLineColor" => "#999999",
          "showValues" => "0",
          "divlineColor" => "#999999",
          "divLineIsDashed" => "1",
          "showAlternateHGridColor" => "0"
        )
    );

    $arrData["data"] = array();

    // Push the data into the array
    while($row = mysqli_fetch_array($result)) {
      array_push($arrData["data"], array(
          "label" => $row["tournamentName"],
          "value" => $row["gameLength"]
          )
      );
    }
	$lengths = array();
	foreach ($arrData["data"] as $key => $row) {
   	 	$lengths[$key] = $row['value'];
	}
	array_multisort($lengths, SORT_ASC, $arrData["data"]);
    /*JSON Encode the data to retrieve the string containing the JSON representation of the data in the array. */

    $jsonEncodedData = json_encode($arrData);

    /*Create an object for the column chart using the FusionCharts PHP class constructor. Syntax for the constructor is ` FusionCharts("type of chart", "unique chart id", width of the chart, height of the chart, "div id to render the chart", "data format", "data source")`. Because we are using JSON data to render the chart, the data format will be `json`. The variable `$jsonEncodeData` holds all the JSON data for the chart, and will be passed as the value for the data source parameter of the constructor.*/

    $columnChart = new FusionCharts("column2D", "myFirstChart" , 600, 300, "chart-1", "json", $jsonEncodedData);

    // Render the chart
    $columnChart->render();
		
}
		$qry = "select teamName as team, avg(kills) as kills, avg(deaths) as deaths, avg(assists) as assists from teamGameStats, teamRoster, team, game, tournament, region, regionTournamentRegistration
						where teamRoster.teamRosterID = teamGameStats.teamRosterID
						and teamRoster.teamID = team.teamID
						and game.gameID = teamGameStats.gameID
						and game.tournamentID = tournament.tournamentID
						and tournament.tournamentYear = 2017
						and tournament.type != \"Promotion\"
						and tournament.tournamentID = regionTournamentRegistration.tournamentID
						and region.regionName = regionTournamentRegistration.regionName
						and region.regionName = \"NALCS\"
						group by teamName;";
			
	$result2 = mysqli_query($conn, $qry);
		  if ($result2) {
    // The `$arrData` array holds the chart attributes and data
    $arrData2 = array(
      "chart" => array(
          "caption" => "Average Team KDA in NALCS 2017",
					"xaxisname" => "Team",
					"yaxisname" => "Average Team KDA",
					"setAdaptiveYMin" => "1",
          "paletteColors" => "#0075c2",
          "bgColor" => "#ffffff",
          "borderAlpha"=> "20",
          "canvasBorderAlpha"=> "0",
          "usePlotGradientColor"=> "0",
          "plotBorderAlpha"=> "10",
          "showXAxisLine"=> "1",
          "xAxisLineColor" => "#999999",
          "showValues" => "0",
          "divlineColor" => "#999999",
          "divLineIsDashed" => "1",
          "showAlternateHGridColor" => "0"
        )
    );

    $arrData2["data"] = array();

    // Push the data into the array
    while($row = mysqli_fetch_array($result2)) {
			$deaths = $row["deaths"];
			if($deaths == 0){
				$deaths = 1;
			}
      array_push($arrData2["data"], array(
          "label" => $row["team"],
          "value" => ($row["kills"] + $row["assists"]) / $deaths));
	}


	$lengths2 = array();
	foreach ($arrData2["data"] as $key => $row) {
   	 	$lengths2[$key] = $row['value'];
	}
	array_multisort($lengths2, SORT_ASC, $arrData2["data"]);
    /*JSON Encode the data to retrieve the string containing the JSON representation of the data in the array. */

    $jsonEncodedData2 = json_encode($arrData2);

    /*Create an object for the column chart using the FusionCharts PHP class constructor. Syntax for the constructor is ` FusionCharts("type of chart", "unique chart id", width of the chart, height of the chart, "div id to render the chart", "data format", "data source")`. Because we are using JSON data to render the chart, the data format will be `json`. The variable `$jsonEncodeData` holds all the JSON data for the chart, and will be passed as the value for the data source parameter of the constructor.*/

    $columnChart2 = new FusionCharts("column2D", "myFirstChart2" , 600, 300, "chart-2", "json", $jsonEncodedData2);

    // Render the chart
    $columnChart2->render();
}
		
		?>
		<div id="chart-1"></div>
		<div id="chart-2"></div>
		<?php
		break;
		
		case "adhoc":
		?>
		<html>
		<br>
		<body>
			<form action="index.php" method="post">
				<table>
					<tr>
						<td>&emsp;Query: </td>
						<td><input type="text" name="query"><br></td>
						<td><input value="Clear" type="reset"></td>
						<td><input type="submit" name="submitted" value="Execute Query"></td>
					</tr>
				</table>
			</form>
		</body>

		</html>
		<?php
		goto results;
		break;
		
		case "results":
			results:
				$type = explode(" ", $qry);
				switch(strtolower($type[0])){
					case "select":
						$result = mysqli_query($conn, $qry);
						if (mysqli_num_rows($result) > 0) {
							$fields = $result->fetch_fields();
							echo "<br><div class=\"col-md-6\"><table class = \"table table-bordered\">";
							echo "<thead class = \"thead-dark\">";
							echo "<tr>";
							foreach($fields as $field){
								echo "<th>" . $field->name . "</th>\n";
							}
							echo "</tr></thead>";
							// output data of each row
							while($row = mysqli_fetch_row($result)) {
								echo "<tr><th scope=\"row\">".$row[0]."</th>";
								foreach(array_slice($row, 1) as $element){
									echo "<td>" . $element . "</td>\n";
								}
								echo "</tr>";
							}
							echo "</table>\n</div>";
						} else {
							echo "0 results";
						}
					break;

					case "insert":
						if (mysqli_query($conn, $qry)) {
							echo "New record created successfully";
						} else {
							echo "Error: " . $qry. "<br>" . mysqli_error($conn);
						}
					break;

					case "delete":
						if (mysqli_query($conn, $qry)) {
							echo "Record deleted successfully";
						} else {
							echo "Error: " . $qry. "<br>" . mysqli_error($conn);
						}
					break;

					case "update":
						if (mysqli_query($conn, $qry)) {
							echo "Record updated successfully";
						} else {
							echo "Error: " . $qry. "<br>" . mysqli_error($conn);
						}
					break;
						
					default:
						if (mysqli_query($conn, $qry)) {
							echo "Query executed successfully";
						} else {
							echo "Error: " . $qry. "<br>" . mysqli_error($conn);
						}	
				}
		break;
	
		default:
				?>
	<br>
	&emsp;<b>Team Members: </b>Nick Hjelle, Connor Noblat, Olsen Ong<br>
<hr>
<ul>
<li><b>Relations:</b><br>
<ol>
	<?php
		$relations = array("region", "tournament", "regionTournamentRegistration",
											"team", "regionTeamRegistration", "teamTournamentRegistration",
											"player", "contract", "game", "teamRoster", "playerGameStats",
											"teamGameStats", "draft");
		foreach($relations as $relation){
			?>
	<li>
	       <form action="index.php" method="post">
				<input type="hidden" name="status" value="results">
				<input type="hidden" name="query" value="select * from <?php echo $relation; ?>;">
					 <a href="javascript:;" onclick="parentNode.submit();"><?php echo $relation; ?></a>
				</form>
	</li>
	<?php
		}
		?>
	</ol>
<br>
<hr>
</li><li><b>Queries:</b><br>
<ol>
<li><a href="javascript:;" onclick="document.getElementById('query1').submit()">Query 1</a>: Find the champion that was banned most frequently.
</li><li><a href="javascript:;" onclick="document.getElementById('query2').submit()">Query 2</a>: Find the players that were at both the summer and spring playoffs in 2015.
</li><li><a href="javascript:;" onclick="document.getElementById('query3').submit()">Query 3</a>: Find the player that has gone to the most tournaments.
</li><li><a href="javascript:;" onclick="document.getElementById('query4').submit()">Query 4</a>: List teams who played more than 100 games.
</li><li><a href="javascript:;" onclick="document.getElementById('query5').submit()">Query 5</a>: List all teams in the NALCS and the NALCS playoffs tournaments that they attended in 2017.
</li></ol>
<br>
<hr></li><li><b>Ad-hoc Query:</b><br>
			<form action="index.php" method="post">
				<table>
					<tr>
						<td>Query: </td>
						<td><input type="text" name="query"><br></td>
						<td><input value="Clear" type="reset"></td>
						<td><input type="submit" name="submitted" value="Execute Query"></td>
					</tr>
				</table>
			</form>
</i></li></ul><i>
<p></p>
</i></font><i><i>
<hr noshade="noshade" size="2">
<p></p>

	<?php
		break;
}

mysqli_close($conn);
?>
</div>
	</div>
</html>