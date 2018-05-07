<?php 

require 'model.php';
$vessels = getVessels();
$pays = getCountries();
$total = getTotal();
$types = getTypes();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    
    <title>Website title</title>
    <meta name="description" content="The HTML5 Herald">
    <meta name="author" content="Baptiste Bisson">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.0/dist/leaflet.css" />
    <link rel="stylesheet" href="css/cluster.css" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/css/bootstrap.min.css" integrity="sha384-Zug+QiDoJOrZ5t4lssLdxGhVrurbmBWopoEl+M6BdEfwnCJZtKxi1KgxUyJq13dy" crossorigin="anonymous">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700|Material+Icons">
    <link rel="stylesheet" href="css/notyf.min.css">
    <link rel="stylesheet" href="css/style.css">
    
    <!--[if lt IE 9]>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script>
    <![endif]-->
</head>

<body>
    <div class="container-fluid map">
        <div class="filtres" id="sidebar">
            <h2>Filters</h2>
            <div class="close" onclick="closeSidebar()">
                <i class="material-icons">close</i>
            </div>
            <div class="contenu">
                <div class="form">
                    <div class="form-group">
                        <label for="nom">Name <button type="button" class="btn btn-primary btn-sm help" data-help="1">Help</button></label>
                        <input type="text" class="form-control" id="nom" placeholder="ex: Heliotrope">
                        <div class="help hide-help" data-help="1">
                            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="annee">Year</label>
                        <input type="number" class="form-control" id="annee" placeholder="ex: 1978">
                    </div>
                    <div class="form-group">
                        <label for="types">Type</label>
                        <select class="form-control select2" id="types" multiple="multiple">
                            <?php foreach ($types as $key => $value): ?>
                                <option value="<?php echo $key ?>"><?php echo $value ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="pavillons">Pavillons</label>
                        <select class="form-control select2" id="pavillons" multiple="multiple">
                            <?php foreach ($pays as $key => $value): ?>
                                <option value="<?php echo $key ?>"><?php echo $value ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" id="submit-form" onclick="submitForm()" class="btn btn-outline-primary" title="Search a boat wreck">Search</button>
                    <button type="button" id="reset" onclick="resetView()" class="btn btn-outline-primary" title="Show all"><i class="material-icons">history</i></button>
                    <button type="button" disabled id="find-area" onclick="findArea()" class="btn btn-outline-primary" title="Find wreck into the visible area"><i class="material-icons">location_searching</i> Fin in area</button>
                </div>
                
                
                <footer class="filtre-footer">
                    <div class="listenavire">
                        <div class="total"></div>
                        <ul class="liste-epaves"></ul>
                    </div>
                    <div class="no-data-container hide"></div>
                    <div class="options">
                        <button type="button" class="btn btn-outline-secondary"><i class="material-icons">arrow_back</i> Home</button>
                    </div>
                </footer>
            </div>
        </div>
        <div class="map col-md-12" id="map">
            <div id="sliderMenu" onclick="openSidebar()">
                <i class="material-icons">drag_handle</i>
            </div>
            <div id="world" onclick="fitMap()" title="Zoom on all boats"></div>
        </div>
    </div>

    <script src="js/jquery-3.2.1.min.js"></script>    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.3/js/bootstrap.min.js" integrity="sha384-a5N7Y/aK3qNeh15eJKGWxsqtnX/wWdSZSKp+81YjTmS15nvnvxKHuzaWwXHDli+4" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/leaflet@1.3.0/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>
    <script src="js/cluster.js"></script>
    <script src="js/notyf.min.js"></script>
    
    <script type="text/javascript">
        var total = <?php echo $total[0]; ?>
        
        var vesselsTmp = '<?php echo $vessels; ?>'
    </script>
    <script src="js/plugin.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
