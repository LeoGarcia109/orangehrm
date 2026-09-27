<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace OrangeHRM\Attendance\Service\Assessment;

/**
 * BR: the statements of the behavioural inventories, fixed and versioned.
 *
 * They are not editable on purpose: a validated statement that somebody
 * "improves" stops measuring what it measured, and old results stop being
 * comparable. A change is a new version.
 *
 * Big Five -- IPIP 50-item Big-Five Factor Markers (Goldberg, 1992), public
 * domain (https://ipip.ori.org). Keying from ipip.ori.org/newBigFive5broadKey.htm.
 * Text: Brazilian translation by Keila Brockveld (IPIP, 100-item markers,
 * ipip.ori.org/Portuguese100-itemBigFiveFactorMarkers.htm); "Am exacting in my
 * work" is absent there and comes from the Portuguese translation by Joao P.
 * Oliveira (Portuguese50-itemBigFiveFactorMarkers.htm). Spelling fixed
 * ("queito", "pelo problemas", "ideias", "as emocoes"). N is scored as
 * Emotional Stability: the keyed-minus items are the neurotic ones.
 *
 * DISC -- statements written for this system after Marston's public model.
 * Not the commercial DiSC; screens call it an approximation.
 */
final class InventoryCatalog
{
    public const BIG5 = 'BIG5';
    public const DISC = 'DISC';
    public const INSTRUMENTS = [self::BIG5, self::DISC];

    private const VERSIONS = [
        self::BIG5 => 'IPIP50-PT-v1',
        self::DISC => 'DISC-HRR-v1',
    ];

    private const FACTORS = [
        self::BIG5 => ['E', 'A', 'C', 'N', 'O'],
        self::DISC => ['D', 'I', 'S', 'C'],
    ];

    /** [code, instrument, factor, reverse-keyed, text] */
    private const ITEMS = [
        ['B5-E01', 'BIG5', 'E', false, 'Eu sou a alma da festa.'],
        ['B5-E02', 'BIG5', 'E', false, 'Eu me sinto confortável quando junto das pessoas.'],
        ['B5-E03', 'BIG5', 'E', false, 'Eu inicio conversas.'],
        ['B5-E04', 'BIG5', 'E', false, 'Eu converso com várias pessoas em festas ou outras reuniões sociais.'],
        ['B5-E05', 'BIG5', 'E', false, 'Eu não me importo de ser o centro das atenções.'],
        ['B5-E06', 'BIG5', 'E', true, 'Eu não falo muito.'],
        ['B5-E07', 'BIG5', 'E', true, 'Eu não costumo me expor muito.'],
        ['B5-E08', 'BIG5', 'E', true, 'Eu tenho pouco a dizer.'],
        ['B5-E09', 'BIG5', 'E', true, 'Eu não gosto de chamar atenção para mim mesmo.'],
        ['B5-E10', 'BIG5', 'E', true, 'Eu fico quieto(a) quando perto de estranhos.'],
        ['B5-A01', 'BIG5', 'A', false, 'Eu desejo saber mais sobre as pessoas.'],
        ['B5-A02', 'BIG5', 'A', false, 'Eu sou solidário(a) aos sentimentos dos outros.'],
        ['B5-A03', 'BIG5', 'A', false, 'Eu tenho um coração mole.'],
        ['B5-A04', 'BIG5', 'A', false, 'Eu dedico tempo aos outros.'],
        ['B5-A05', 'BIG5', 'A', false, 'Eu sou sensível às emoções das outras pessoas.'],
        ['B5-A06', 'BIG5', 'A', false, 'Eu faço as outras pessoas se sentirem à vontade.'],
        ['B5-A07', 'BIG5', 'A', true, 'Eu não estou realmente interessado(a) nos outros.'],
        ['B5-A08', 'BIG5', 'A', true, 'Eu sou grosseiro(a) com as pessoas.'],
        ['B5-A09', 'BIG5', 'A', true, 'Eu não tenho interesse pelos problemas dos outros.'],
        ['B5-A10', 'BIG5', 'A', true, 'Eu sinto pouca preocupação pelos outros.'],
        ['B5-C01', 'BIG5', 'C', false, 'Eu estou sempre pronto(a).'],
        ['B5-C02', 'BIG5', 'C', false, 'Eu presto atenção aos detalhes.'],
        ['B5-C03', 'BIG5', 'C', false, 'Eu cumpro minhas tarefas imediatamente.'],
        ['B5-C04', 'BIG5', 'C', false, 'Eu gosto de ordem, de organização.'],
        ['B5-C05', 'BIG5', 'C', false, 'Eu sigo uma agenda, uma rotina de tarefas.'],
        ['B5-C06', 'BIG5', 'C', false, 'Eu sou exigente no meu trabalho.'],
        ['B5-C07', 'BIG5', 'C', true, 'Eu largo minhas coisas em qualquer lugar.'],
        ['B5-C08', 'BIG5', 'C', true, 'Eu faço uma bagunça com as minhas coisas.'],
        ['B5-C09', 'BIG5', 'C', true, 'Frequentemente eu me esqueço de devolver as coisas aos seus devidos lugares.'],
        ['B5-C10', 'BIG5', 'C', true, 'Eu não cumpro com minhas obrigações.'],
        ['B5-N01', 'BIG5', 'N', false, 'Eu me sinto descontraído(a), leve, solto(a) a maior parte do tempo.'],
        ['B5-N02', 'BIG5', 'N', false, 'Raramente eu me sinto triste.'],
        ['B5-N03', 'BIG5', 'N', true, 'Eu me estresso facilmente.'],
        ['B5-N04', 'BIG5', 'N', true, 'Eu me preocupo com as coisas.'],
        ['B5-N05', 'BIG5', 'N', true, 'Eu me sinto facilmente incomodado(a).'],
        ['B5-N06', 'BIG5', 'N', true, 'Eu me aborreço facilmente.'],
        ['B5-N07', 'BIG5', 'N', true, 'Meu humor muda frequentemente.'],
        ['B5-N08', 'BIG5', 'N', true, 'Eu tenho mudanças frequentes de humor.'],
        ['B5-N09', 'BIG5', 'N', true, 'Eu me irrito facilmente.'],
        ['B5-N10', 'BIG5', 'N', true, 'Frequentemente eu me sinto triste.'],
        ['B5-O01', 'BIG5', 'O', false, 'Eu tenho um vocabulário rico.'],
        ['B5-O02', 'BIG5', 'O', false, 'Eu tenho uma imaginação viva.'],
        ['B5-O03', 'BIG5', 'O', false, 'Eu tenho ideias excelentes.'],
        ['B5-O04', 'BIG5', 'O', false, 'Eu entendo as coisas rapidamente.'],
        ['B5-O05', 'BIG5', 'O', false, 'Eu faço uso de palavras difíceis ou incomuns.'],
        ['B5-O06', 'BIG5', 'O', false, 'Eu passo meu tempo refletindo sobre as coisas.'],
        ['B5-O07', 'BIG5', 'O', false, 'Eu sou cheio(a) de ideias.'],
        ['B5-O08', 'BIG5', 'O', true, 'Eu tenho dificuldade para entender ideias abstratas.'],
        ['B5-O09', 'BIG5', 'O', true, 'Eu não me interesso por ideias abstratas.'],
        ['B5-O10', 'BIG5', 'O', true, 'Eu não tenho uma boa imaginação.'],
        ['DISC-D01', 'DISC', 'D', false, 'Gosto de assumir o comando quando algo precisa ser resolvido.'],
        ['DISC-D02', 'DISC', 'D', false, 'Tomo decisões rápidas, mesmo sem ter todas as informações.'],
        ['DISC-D03', 'DISC', 'D', false, 'Encaro desafios difíceis como algo que me motiva.'],
        ['DISC-D04', 'DISC', 'D', false, 'Sou direto(a) ao dizer o que penso.'],
        ['DISC-D05', 'DISC', 'D', false, 'Gosto de metas claras e de ver resultados.'],
        ['DISC-D06', 'DISC', 'D', false, 'Fico impaciente quando as coisas andam devagar.'],
        ['DISC-I01', 'DISC', 'I', false, 'Faço amizade com facilidade com pessoas novas.'],
        ['DISC-I02', 'DISC', 'I', false, 'Gosto de animar e motivar as pessoas ao meu redor.'],
        ['DISC-I03', 'DISC', 'I', false, 'Fico à vontade conversando com clientes e desconhecidos.'],
        ['DISC-I04', 'DISC', 'I', false, 'Prefiro trabalhar em equipe a trabalhar sozinho(a).'],
        ['DISC-I05', 'DISC', 'I', false, 'Costumo convencer as pessoas com entusiasmo.'],
        ['DISC-I06', 'DISC', 'I', false, 'Continuo otimista mesmo quando as coisas dão errado.'],
        ['DISC-S01', 'DISC', 'S', false, 'Prefiro uma rotina estável a mudanças frequentes.'],
        ['DISC-S02', 'DISC', 'S', false, 'Sou paciente, mesmo com clientes difíceis.'],
        ['DISC-S03', 'DISC', 'S', false, 'Ajudo os colegas sem precisar que me peçam.'],
        ['DISC-S04', 'DISC', 'S', false, 'Mantenho a calma quando o ambiente fica tenso.'],
        ['DISC-S05', 'DISC', 'S', false, 'Sou leal às pessoas e ao lugar onde trabalho.'],
        ['DISC-S06', 'DISC', 'S', false, 'Prefiro terminar uma tarefa antes de começar outra.'],
        ['DISC-C01', 'DISC', 'C', false, 'Sigo as regras e os procedimentos à risca.'],
        ['DISC-C02', 'DISC', 'C', false, 'Confiro meu trabalho mais de uma vez antes de entregar.'],
        ['DISC-C03', 'DISC', 'C', false, 'Gosto de entender todos os detalhes antes de decidir.'],
        ['DISC-C04', 'DISC', 'C', false, 'Prefiro fatos e números a opiniões.'],
        ['DISC-C05', 'DISC', 'C', false, 'Me incomoda quando algo é feito sem qualidade.'],
        ['DISC-C06', 'DISC', 'C', false, 'Planejo minhas tarefas antes de começar.'],
    ];

    /**
     * @return array<int, array{code: string, instrument: string, factor: string, reverse: bool, text: string}>
     */
    public static function items(string $instrument): array
    {
        return array_values(array_map(
            [self::class, 'toItem'],
            array_filter(self::ITEMS, static fn (array $row) => $row[1] === $instrument)
        ));
    }

    public static function item(string $code): ?array
    {
        foreach (self::ITEMS as $row) {
            if ($row[0] === $code) {
                return self::toItem($row);
            }
        }
        return null;
    }

    /**
     * @return string[]
     */
    public static function factors(string $instrument): array
    {
        return self::FACTORS[$instrument] ?? [];
    }

    public static function version(string $instrument): string
    {
        return self::VERSIONS[$instrument];
    }

    /**
     * The order the statements are shown in: each instrument rotates through
     * its factors, and the two are woven together -- two Big Five statements,
     * then one DISC -- so no factor comes in a block. Independent of the order
     * the instruments are listed in, so a stored answer set always lines up.
     *
     * @param string[] $instruments
     * @return string[] item codes
     */
    public static function sequence(array $instruments): array
    {
        $big5 = in_array(self::BIG5, $instruments, true) ? self::rotated(self::BIG5) : [];
        $disc = in_array(self::DISC, $instruments, true) ? self::rotated(self::DISC) : [];

        $sequence = [];
        while ($big5 !== [] || $disc !== []) {
            foreach ([array_shift($big5), array_shift($big5), array_shift($disc)] as $code) {
                if ($code !== null) {
                    $sequence[] = $code;
                }
            }
        }
        return $sequence;
    }

    /**
     * @param string[] $instruments
     * @return string[][]
     */
    public static function pages(array $instruments, int $size = 10): array
    {
        return array_chunk(self::sequence($instruments), $size);
    }

    /**
     * The instrument's items, one factor at a time in turn: E1, A1, C1, N1, O1, E2...
     *
     * @return string[]
     */
    private static function rotated(string $instrument): array
    {
        $byFactor = [];
        foreach (self::items($instrument) as $item) {
            $byFactor[$item['factor']][] = $item['code'];
        }
        $rotated = [];
        for ($i = 0, $n = max(array_map('count', $byFactor)); $i < $n; $i++) {
            foreach (self::FACTORS[$instrument] as $factor) {
                if (isset($byFactor[$factor][$i])) {
                    $rotated[] = $byFactor[$factor][$i];
                }
            }
        }
        return $rotated;
    }

    private static function toItem(array $row): array
    {
        return [
            'code' => $row[0],
            'instrument' => $row[1],
            'factor' => $row[2],
            'reverse' => $row[3],
            'text' => $row[4],
        ];
    }
}

