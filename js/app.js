angular.module('Dota2Draft', ['Dota2DraftServices', 'Dota2DraftFilters']).
  config(['$routeProvider', function($routeProvider) {
    $routeProvider.
      when('/', { templateUrl: 'templates/home.html', controller: CreateMatchCtrl }).
      when('/match/:matchId', { templateUrl: 'templates/match.html', controller: MatchCtrl }).
      otherwise({ redirectTo: '/' });
}]);

angular.module('Dota2DraftServices', ['ngResource']).
  factory('Match', function($resource){
    return $resource('match/:matchId', {}, {});
});

angular.module('Dota2DraftFilters', []).
  filter('HeroImage', function () {
    return function (text) {
      return text.substring(14);
    }
  });

function CreateMatchCtrl($scope, $location, Match) {
  $scope.submit = function() {
    $scope.match = new Match({ type: 'BanningDraft' });
    $scope.match.$save(function(match) {
      $scope.gameLinkShow = true;
      $scope.gameReadLink = '/match/' + match.readKey;
      $scope.gameEditLink = '/match/' + match.editKey;
    });
  };
  
  $scope.viewMatch = function(id) {
    $location.path('/match/' + id);
  }
  
  $scope.gameLinkShow = false;
  $scope.match = null;
}

function MatchCtrl($scope, $routeParams, Match) {
  $scope.phase = 0;
  $scope.message = 'Radiant banning phase.';
  $scope.radiant_heroes = [];
  $scope.dire_heroes = [];
  $scope.pool_heroes = [];
  $scope.radiant_class = 'picking';
  $scope.dire_class = '';
  
  $scope.banHero = function(hero) {
    console.log(hero);
  }
  
  $scope.phaseUpdated = function() {
    switch($scope.phase) {
      case 0: case 2: 
        $scope.message = 'Radiant banning phase.';
        break;
      case 4: case 7: case 8: case 11: case 12:
        $scope.message = 'Radiant picking phase.';
        break;
      case 1: case 3:
          $scope.message = 'Dire banning phase.';
          break;
      case 5: case 6: case 9: case 10: case 13:
        $scope.message = 'Dire picking phase.';
        break;
    }
    switch($scope.phase) {
      case 0: case 2: case 4: case 7: case 8: case 11: case 12:
        $scope.radiant_class = 'picking';
        $scope.dire_class = '';
        break;
      
      case 1: case 3: case 5: case 6: case 9: case 10: case 13:
        $scope.radiant_class = '';
        $scope.dire_class = 'picking';
        break;
      default:
        $scope.radiant_class = '';
        $scope.dire_class = '';
        $scope.message = 'Hero selection complete. Good luck! Have fun!';
        for(var i = 0; i < $scope.pool_heroes.length; i++) {
          if($scope.pool_heroes[i].status == 'pool') {
            $scope.pool_heroes[i].status = 'surplus';
          }
        }
        break;
    }
  }
  
  $scope.pickHero = function(hero, team) {
    if(team === 0) {
      hero.status = 'radiant';
      $scope.radiant_heroes.push(hero);
    } else {
      hero.status = 'dire';
      $scope.dire_heroes.push(hero);
    }
  }
  
  $scope.clickHero = function(hero) {
    if($scope.phase > 13 || hero.status !== 'pool' || $scope.client_status === 'spectator') {
      return;
    }
    if($scope.phase < 4) {
      $scope.banHero(hero);
      hero.status = 'banned';
    } else {
      switch($scope.phase) {
        case 4: case 7: case 8: case 11: case 12:
          $scope.pickHero(hero, 0);
          break;
        case 5: case 6: case 9: case 10: case 13:
          $scope.pickHero(hero, 1);
          break;
        default:
          $scope.radiant_class = '';
          $scope.dire_class = '';
          break;
      }
    }
    
    $scope.phase++;
    $scope.phaseUpdated();
  }

  Match.get({ matchId: $routeParams.matchId }, function(match) {
    $scope.match = match;
    $scope.pool_heroes = $scope.match.heroes;
    
    if($scope.match.hasOwnProperty('editKey')) {
      $scope.client_status = 'editor';
    } else {
      $scope.client_status = 'spectator';
    }
    
    for(var i = 0; i < $scope.pool_heroes.length; i++) {
      if($scope.pool_heroes[i].status != 'pool') {
        $scope.phase++;
        switch($scope.pool_heroes[i].status) {
          case 'radiant':
            $scope.pickHero($scope.pool_heroes[i], 0);
            break;
          case 'dire':
            $scope.pickHero($scope.pool_heroes[i], 1);
            break;
        }
      }
    }
    $scope.phaseUpdated();
  });
}