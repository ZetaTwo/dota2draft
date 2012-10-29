angular.module('Dota2Draft', ['Dota2DraftServices', 'Dota2DraftFilters']).
  config(['$routeProvider', function($routeProvider) {
    $routeProvider.
      when('/', { templateUrl: 'templates/home.html', controller: CreateMatchCtrl }).
      when('/match/:matchId', { templateUrl: 'templates/match.html', controller: MatchCtrl }).
      otherwise({ redirectTo: '/' });
}]);

angular.module('Dota2DraftServices', ['ngResource']).
  factory('Match', function($resource) {
    return $resource('match/:matchId', { matchId: '@id' }, {});
  }).
  factory('MatchHero', function($resource) {
    return $resource('matchhero/:matchHeroId', { matchHeroId: '@id' }, {});
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
    });
  };
  
  $scope.viewMatch = function(id) {
    $location.path('/match/' + id);
  }
  
  $scope.gameLinkShow = false;
  $scope.match = null;
}

function MatchCtrl($scope, $routeParams, Match, MatchHero) {
  $scope.phase = 0;
  $scope.message = 'Radiant banning phase.';
  $scope.radiant_heroes = [];
  $scope.dire_heroes = [];
  $scope.pool_heroes = [];
  $scope.radiant_class = 'picking';
  $scope.dire_class = '';
  
  //Create Pubnub object
  $scope.pubnub = PUBNUB.init({
    subscribe_key : 'sub-31bdff4f-8afa-11e1-b98e-3955e0de91cc',
    origin        : 'pubsub.pubnub.com',
  });

  //Sync UI
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
  
  //Click hero
  $scope.clickHero = function(hero) {
    if($scope.phase > 13 || hero.status !== 'pool' || $scope.client_status === 'spectator') {
      return;
    }
    
    if($scope.phase < 4) {
      hero.status = 'banned';
    } else {
      switch($scope.phase) {
        case 4: case 7: case 8: case 11: case 12:
          hero.status = 'radiant';
          break;
        case 5: case 6: case 9: case 10: case 13:
          hero.status = 'dire';
          break;
        default:
          break;
      }
    }
    
    var matchhero = new MatchHero({ id: hero.match_hero_id, status: hero.status, editKey: $scope.match.editKey });
    matchhero.$save();
  }
  
  //Choose hero
  $scope.chooseHero = function(hero) {
    if($scope.phase < 4) {
    } else {
      switch($scope.phase) {
        case 4: case 7: case 8: case 11: case 12:
          $scope.radiant_heroes.push(hero);
          break;
        case 5: case 6: case 9: case 10: case 13:
          $scope.dire_heroes.push(hero);
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

  //Retrieve match
  Match.get({ matchId: $routeParams.matchId }, function(match) {
    $scope.match = match;
    $scope.pool_heroes = $scope.match.heroes;
    
    //Assign role
    if($scope.match.hasOwnProperty('editKey')) {
      $scope.client_status = 'editor';
    } else {
      $scope.client_status = 'spectator';
    }
    
    //Subscribe to updates
    $scope.pubnub.subscribe({
      channel  : 'dota2draft_' + $scope.match.readKey,
      connect  : function() { console.log('Connected to ' + 'dota2draft_' + $scope.match.readKey); },
      callback : function(message) {
        for(var i = 0; i < $scope.pool_heroes.length; i++) {
          if($scope.pool_heroes[i].match_hero_id == message.id) {
            $scope.pool_heroes[i].status = message.status;
            $scope.$apply($scope.chooseHero($scope.pool_heroes[i], false));
          }
        }
      }
    });
    
    //Fast forward to current state
    for(var i = 0; i < $scope.pool_heroes.length; i++) {
      if($scope.pool_heroes[i].status != 'pool') {
        $scope.phase++;
        if($scope.pool_heroes[i].status === 'radiant') {
          $scope.radiant_heroes.push($scope.pool_heroes[i]);
        } else if($scope.pool_heroes[i].status === 'dire') {
          $scope.dire_heroes.push($scope.pool_heroes[i]);
        }
      }
    }
    $scope.phaseUpdated();
  });
}