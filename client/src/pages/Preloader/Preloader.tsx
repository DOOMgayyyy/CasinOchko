import React, { useEffect } from "react";
import { IBasePage, PAGES } from "../PageManager";

import './Preloader.scss';

const Preloader: React.FC<IBasePage> = (props: IBasePage) => {
    const { setPage } = props;

    useEffect(() => {
        setTimeout(() => setPage(PAGES.LOGIN), 1);// стояло 3000, но нам это пока не особо надо
    });

    return (
        <div className="preloader">
            <div className="preloader-wrapper"></div>
            <div>
                <div className="preloader__dots" />
            </div>
            <span>Загрузка...</span>
            <section className="preloader__authors">
                <h1>Автор:</h1>
                <div className="authors_name victor"><span>Виктор Мурин</span></div>
            </section>
        </div>
    );
}

export default Preloader;