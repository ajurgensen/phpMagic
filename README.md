phpMagic

Takes Propel ORM Object and creates a bootstrap form - Also build lists and whole pages
 - does XSS security
 - Adds related tables automagiclly
 - Option to add other tables as needed (many-to-many)
 - Renaming of colum names
 - Excluding colums
 - Client Side Validation
 - Server Side Validation
 - Validation Callbacks
 - Use to create lists of objects
 - Use to create whole pages (with auth etc)

Some ways of using phpMagic

    //Edit for User 
    use \ajurgensen\phpMagic\formMagic;
    if (!$userid || !$user = UserQuery::create()->findOneById($userid))
    {
      $user = new User();
    }
    $names['UGLY_COLUM_NAME'] = 'This is a nicer name';
    $options['FM_EXCLUDE'] = array ('PASSWORDHASH','UPDATED_AT','CREATED_AT');
    $fm = new formMagic($user,$options,$names);
    if ($fm->entitySaved)
    {
        //all good, user updated - now redirect to some other page (entity is saved, etc) 
    }else
    {
        //Show the form
        echo $fm->html;
    }

Another Way

    //Build a page with a list of objects
    use \ajurgensen\phpMagic\formMagic;
    use \ajurgensen\phpMagic\pageMagic;
    $menu = array(
    'Menu Point One' =>array('subpoint one' => '/one/one','subpoint two' => '/one/two'),
    'Menu Point Two' =>array('subpoint one' => '/two/one','subpoint two' => '/two/two'));
    $pm = new pageMagic('List Entities');
    $pm->addMenu($menu);
    $entites = EntityQuery::create()->find();
    foreach($entites as &$entity)
    {
        $entity->link = "/edit/entity/" . $entity->getId();
    }
    $options['LM_LINK'] = array('name');
    $options['LM_ADDNEW'] = "/edit/entity/0";
    $lm = new listMagic($entities,$options);
    if ($lm->HTMLready)
    {
        $pm->addHtml($lm->getHTML());
    }
    $pm->finalize();
    }
