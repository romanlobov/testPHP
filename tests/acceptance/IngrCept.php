<?php
use Page\Ingredients as IngredientsPage;
use Helper\Common;
class S01_IngredientsCest {
// нужно выносить генератов в helper
    private  $ingredientName = null;
    private  $ingredientDigistarCode =  null;
    private  $ingredientDryMatter =  null;
    private  $ingredientPrice =  null;
    private  $ingredientDescription =  null;
    private  $ingredients_list = null;
    // перед каждым тестом  формируем рандомные тестовые данные для создания сущности. каждый тест в этом сьюте  - разные
    // перед каждым тестом берем данные из бд, чтобы убедиться в их наличии
    public function _beforeTests(AcceptanceTester $I)
    {
    }
    public function _afterSuite(AcceptanceTester $I)
    {
    }
    public function _before(AcceptanceTester $I)
    {
        $this->ingredientName = 'Ингредиент ' . date('h:i:s');
        $this->ingredientDigistarCode = 'd' . rand(3, 100000) . rand(2, 100000);
        $this->ingredientDryMatter = rand(2, 100);
        $this->ingredientPrice = rand(10000, 100000);
        $this->ingredientDescription = 'Описание ингредиента ' . date('l jS \of F Y h:i:s A');
    }
    /* проверка наличия в таблице Ингредиентов заприсей из БД, отсортированных в обратном порядке
     * todo добавить проверку  пагинации на странице если записей в бд более 10
     * todo добавить проверку для КСВ
     */
    public function assertIngredientListExistTest(AcceptanceTester $I,  \Page\Ingredients $ingredientsPage)
    {
        $I->wantTo('See on Page Ingredients info from database');
        $I->amOnPage('/#/pages/ingredients');
        $I->dontSee('Ошибка');
        $I->dontSee('Bad Request');
        $I->dontSee('Error');
        // ждем появления заголовка
        $I->waitForText('ИНГРЕДИЕНТЫ');
        // запрос в БД и получение массива с данными ингредиентов, воду не сравниваем
        $ingredients = $I->getExistingIngredientsList();
        // в цикле проходим по таблице из  10 строк, стравнивая значения из таблицы и из
        //базы построчно, начиная с 1, покольку id = 0 у воды
        $y = 1;
        for ($x = 1; $x < 10; $x++) {
            $name_in_base = trim($ingredients[$x]['Name']);
            $name_in_interface = $I->grabTextFrom($ingredientsPage::$rowInIngredientsTable . '/tr[' . $y . ']/td[2]');
            var_dump($name_in_base);
            $I->assertEquals($name_in_base, $name_in_interface);
            $y++;
        };
        // todo аналогичная проверка для веса  - селект со склада
    }
    /* проверка наличия на карточке  информации из БД
     */
    public function assertIngredientInfoExistTest(AcceptanceTester $I,  \Page\Ingredients $ingredientsPage)
    {
        $I->wantTo('See on Ingredient card info from database');
        $I->amOnPage('/#/pages/ingredients');
        $I->dontSee('Ошибка');
        $I->dontSee('Bad Request');
        $I->dontSee('Error');
        // ждем появления заголовка
        $I->waitForText('ИНГРЕДИЕНТЫ');
        // запрос в БД и получение массива с данными ингредиентов
        $ingredients = $I->getExistingIngredientsList();
        // выбираем первый элемент и кликаем на него
        $I->click($ingredientsPage::$rowInIngredientsTable . '/tr[1]/td[2]');
        $I->seeInField($ingredientsPage::$ingredientNameField,$ingredients[1]['Name']);
        // todo digi star в другой таблице. добавить.
        //$I->seeInField($ingredientsPage::$ingredientDigiStarCodeField,$ingredients[0]['']);
        $I->seeInField($ingredientsPage::$ingredientPriceField,$ingredients[1]['Price']);
        // todo  проанализировать добавление типа и КСВ (в бд он в другом формате)
        //$I->seeInField($ingredientsPage::$ingredientTypeField,$ingredients[0]);
        //$I->seeInField($ingredientsPage::$ingredientDryMatter,$ingredients[0]);
        $I->seeInField($ingredientsPage::$ingredientDescriptionField,$ingredients[1]['Description']);
    }
    /* проверка создания нового ингредиента, включая запись в БД
     */
    public function createNewIngredientTest(AcceptanceTester $I, \Page\Ingredients $ingredientsPage)
    {
        $I->wantTo('Create new Ingredient');
        $I->amOnPage('/#/pages/ingredients');
        $I->dontSee('Ошибка');
        $I->dontSee('Bad Request');
        $I->dontSee('Error');
        $I->waitForText('ИНГРЕДИЕНТЫ');
        //  с помощью созданного page object спрятали под капот айдишники элементов страницы и просто передаем в них названия полей
        $ingredientsPage->addIngredient($this->ingredientName, $this->ingredientDigistarCode, $this->ingredientDryMatter, $this->ingredientPrice, $this->ingredientDescription);
        $I->click($ingredientsPage::$saveButton);
        $I->waitForText('Запись успешно добавлена');
        // todo добавить проверку джойном кода dogistar и добавить ксв
        $I->seeInDatabase('Ingredients', array('Name' => $this->ingredientName));
    }
    /*
     * Создание ингредиента с уже существующим названием
     *
     */
    public function createIngredientAlreadyExistTest(AcceptanceTester $I, \Page\Ingredients $ingredientsPage)
    {
        $I->wantTo('Create Ingredient with same name');
        //$I->reloadPage();
        $I->amOnPage('/#/pages/ingredients');
        $I->waitForText('ИНГРЕДИЕНТЫ');
        // получаем из БД список
        $ingredients = $I->getExistingIngredientsList();
        $ingredientsPage->addIngredient($ingredients[1]['Name'], $this->ingredientDigistarCode, $this->ingredientDryMatter, $this->ingredientPrice, $this->ingredientDescription);
        $I->wait(3);
        $I->click($ingredientsPage::$saveButton);
        $I->waitForText('Произошла ошибка');
    }
    /*
     * Создание ингредиента с динамическим КСВ. Проверка наличия его на главной странице
     */
    public function createIngredientWithDynamicDryMatterTest(AcceptanceTester $I, \Page\Ingredients $ingredientsPage)
    {
        $I->wantTo('Create Ingredient with dynamic DryMatter');
        //$I->reloadPage();
        $I->amOnPage('/#/pages/ingredients');
        $I->waitForText('ИНГРЕДИЕНТЫ');
        $ingredientsPage->addIngredient($this->ingredientName, $this->ingredientDigistarCode, $this->ingredientDryMatter, $this->ingredientPrice, $this->ingredientDescription);
        $I->click($ingredientsPage::$dynamicDryMatterCheckbox); // кнопки Сохранить и чекбокс тоже спрятаны в классе page object Ingredient
        $I->click($ingredientsPage::$saveButton);
        $I->wait(3);
        $I->waitForText('Запись успешно добавлена');
        // todo описать page Главная страница
        $I->amOnPage('/#/pages/dashboard');
        $I->waitForText('ОСТАТКИ ПО ДНЯМ'); // проверяем по надписям, что мы находимся на главной странице.
        $I->waitForText('ТРЕНД АППЕТИТА');
        $I->waitForText($this->ingredientName); // проверяем наличие ингредиента, созданного конкретно в этом тесте
    }
}