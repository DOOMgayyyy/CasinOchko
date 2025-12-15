import React from "react";
import { IBasePage } from "../PageManager";

import './Preloader.scss';

const Preloader: React.FC<IBasePage> = () => {

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