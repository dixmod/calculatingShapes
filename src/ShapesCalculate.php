<?php

namespace Dixmod;

use Dixmod\Shapes\ShapeInterface;
use Exception;
use ReflectionClass;
use Symfony\Component\Console\{
    Command\Command,
    Input\InputArgument,
    Input\InputInterface,
    Output\OutputInterface,
    Question\ChoiceQuestion,
    Question\Question
};

class ShapesCalculate extends Command
{
    protected const TYPES_SHAPES = [
        'Circle',
        'Rectangle',
        'Square',
        'Triangle',
    ];
    protected $input;
    protected $output;
    protected $dialog;

    /**
     *
     */
    protected function configure()
    {
        $this->setName('shape')
            ->setDescription('This command calculates the area and perimeter of geometric shapes')
            ->addArgument(
                'typeShape',
                InputArgument::OPTIONAL,
                'Change geometric shape from list: ' . join(', ', self::TYPES_SHAPES)
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->dialog = $this->getHelper('question');

        $inputTypeShape = $this->askTypeTypeShape();
        $shape = $this->createShape($inputTypeShape);

        $output->writeln('Area:' . $shape->getArea());
        $output->writeln('Perimeter:' . $shape->getPerimeter());
    }

    /**
     * @param $inputTypeShape
     * @return ShapeInterface
     * @throws \ReflectionException
     */
    private function createShape($inputTypeShape): ShapeInterface
    {
        // TODO: Refactor
        $classNameShape = 'Dixmod\\Shapes\\' . $inputTypeShape;
        if (!class_exists($classNameShape)) {
            throw new Exception(
                'Error change type shape'
            );
        }

        // Получение класса фигуры и его конструктора
        $classShape = new ReflectionClass($classNameShape);
        $constructorShape = $classShape->getMethod('__construct');

        // Получение списка параметров конструктора
        $paramsConstructorShape = $constructorShape->getParameters();
        $valuesParamsConstructorShape = [];

        // Запрос у пользователя значений параметров фигуры
        foreach ($paramsConstructorShape as $index => $paramConstructorShape) {
            $valuesParamsConstructorShape[$index] = $this->askValueParam($paramConstructorShape);
        }

        // Создание экземпляра класса фигуры с пользовательскими параметрами
        return $classShape->newInstanceArgs($valuesParamsConstructorShape);
    }

    /**
     * @param $param
     * @return float
     */
    protected function askValueParam($param)
    {
        do {
            $question = new Question(
                '<question>Please input ' . $param->name . ': </question>',
                0
            );

            $valuesParam = $this->dialog->ask($this->input, $this->output, $question);
            $valuesParam = (float)$valuesParam;
        } while (!$this->isValidValuesParam($valuesParam));

        return $valuesParam;
    }

    /**
     * @param $value
     * @return bool
     */
    protected function isValidValuesParam($value)
    {
        return !empty($value);
    }

    /**
     * @param string $inputTypeShape
     * @return bool
     */
    protected function isValidTypeShape(string $inputTypeShape)
    {
        return in_array($inputTypeShape, self::TYPES_SHAPES);
    }

    /**
     * @return string
     */
    private function askTypeTypeShape(): string
    {
        $inputTypeShape = $this->input->getArgument('typeShape');
        $inputTypeShape = ucfirst($inputTypeShape);

        if (!$this->isValidTypeShape($inputTypeShape)) {
            $question = new ChoiceQuestion(
                '<question>Please select figure type:</question>',
                self::TYPES_SHAPES
            );

            $question->setErrorMessage('Shape %s is invalid.');
            $inputTypeShape = $this->dialog->ask($this->input, $this->output, $question);
        }

        return $inputTypeShape;
    }
}
