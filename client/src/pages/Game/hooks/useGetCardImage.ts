// Импорт всех изображений карт
import card2C from '../../../assets/img/deckOfCards/2C.png';
import card2D from '../../../assets/img/deckOfCards/2D.png';
import card2H from '../../../assets/img/deckOfCards/2H.png';
import card2S from '../../../assets/img/deckOfCards/2S.png';
import card3C from '../../../assets/img/deckOfCards/3C.png';
import card3D from '../../../assets/img/deckOfCards/3D.png';
import card3H from '../../../assets/img/deckOfCards/3H.png';
import card3S from '../../../assets/img/deckOfCards/3S.png';
import card4C from '../../../assets/img/deckOfCards/4C.png';
import card4D from '../../../assets/img/deckOfCards/4D.png';
import card4H from '../../../assets/img/deckOfCards/4H.png';
import card4S from '../../../assets/img/deckOfCards/4S.png';
import card5C from '../../../assets/img/deckOfCards/5C.png';
import card5D from '../../../assets/img/deckOfCards/5D.png';
import card5H from '../../../assets/img/deckOfCards/5H.png';
import card5S from '../../../assets/img/deckOfCards/5S.png';
import card6C from '../../../assets/img/deckOfCards/6C.png';
import card6D from '../../../assets/img/deckOfCards/6D.png';
import card6H from '../../../assets/img/deckOfCards/6H.png';
import card6S from '../../../assets/img/deckOfCards/6S.png';
import card7C from '../../../assets/img/deckOfCards/7C.png';
import card7D from '../../../assets/img/deckOfCards/7D.png';
import card7H from '../../../assets/img/deckOfCards/7H.png';
import card7S from '../../../assets/img/deckOfCards/7S.png';
import card8C from '../../../assets/img/deckOfCards/8C.png';
import card8D from '../../../assets/img/deckOfCards/8D.png';
import card8H from '../../../assets/img/deckOfCards/8H.png';
import card8S from '../../../assets/img/deckOfCards/8S.png';
import card9C from '../../../assets/img/deckOfCards/9C.png';
import card9D from '../../../assets/img/deckOfCards/9D.png';
import card9H from '../../../assets/img/deckOfCards/9H.png';
import card9S from '../../../assets/img/deckOfCards/9S.png';
import cardAC from '../../../assets/img/deckOfCards/AC.png';
import cardAD from '../../../assets/img/deckOfCards/AD.png';
import cardAH from '../../../assets/img/deckOfCards/AH.png';
import cardAS from '../../../assets/img/deckOfCards/AS.png';
import cardBC from '../../../assets/img/deckOfCards/BC.png';
import cardBD from '../../../assets/img/deckOfCards/BD.png';
import cardBH from '../../../assets/img/deckOfCards/BH.png';
import cardBS from '../../../assets/img/deckOfCards/BS.png';
import cardCC from '../../../assets/img/deckOfCards/CC.png';
import cardCD from '../../../assets/img/deckOfCards/CD.png';
import cardCH from '../../../assets/img/deckOfCards/CH.png';
import cardCS from '../../../assets/img/deckOfCards/CS.png';
import cardDC from '../../../assets/img/deckOfCards/DC.png';
import cardDD from '../../../assets/img/deckOfCards/DD.png';
import cardDH from '../../../assets/img/deckOfCards/DH.png';
import cardDS from '../../../assets/img/deckOfCards/DS.png';
import cardEC from '../../../assets/img/deckOfCards/EC.png';
import cardED from '../../../assets/img/deckOfCards/ED.png';
import cardEH from '../../../assets/img/deckOfCards/EH.png';
import cardES from '../../../assets/img/deckOfCards/ES.png';

/**
 * Хук для получения изображения карты по её коду
 * Использует статические импорты для оптимальной производительности
 */
const useGetCardImage = () => {
    // Объект-маппинг кодов карт на импортированные изображения
    const cardCodes: { [key: string]: string } = {
        '2C': card2C,
        '2D': card2D,
        '2H': card2H,
        '2S': card2S,
        '3C': card3C,
        '3D': card3D,
        '3H': card3H,
        '3S': card3S,
        '4C': card4C,
        '4D': card4D,
        '4H': card4H,
        '4S': card4S,
        '5C': card5C,
        '5D': card5D,
        '5H': card5H,
        '5S': card5S,
        '6C': card6C,
        '6D': card6D,
        '6H': card6H,
        '6S': card6S,
        '7C': card7C,
        '7D': card7D,
        '7H': card7H,
        '7S': card7S,
        '8C': card8C,
        '8D': card8D,
        '8H': card8H,
        '8S': card8S,
        '9C': card9C,
        '9D': card9D,
        '9H': card9H,
        '9S': card9S,
        'AC': cardAC,
        'AD': cardAD,
        'AH': cardAH,
        'AS': cardAS,
        'BC': cardBC,
        'BD': cardBD,
        'BH': cardBH,
        'BS': cardBS,
        'CC': cardCC,
        'CD': cardCD,
        'CH': cardCH,
        'CS': cardCS,
        'DC': cardDC,
        'DD': cardDD,
        'DH': cardDH,
        'DS': cardDS,
        'EC': cardEC,
        'ED': cardED,
        'EH': cardEH,
        'ES': cardES,
    };

    // Возвращаем функцию для получения изображения по коду карты
    return (cardCode: string): string => cardCodes[cardCode] || '';
};

export default useGetCardImage;

